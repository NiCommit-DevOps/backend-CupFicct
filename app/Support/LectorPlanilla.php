<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Lector de planillas (CSV con BOM o XLSX real) sin librerías externas.
 *
 * Descomprime el XLSX como ZIP, resuelve las cadenas compartidas y recorre la
 * primera hoja; para CSV detecta el separador (',' o ';'). Devuelve cada fila
 * como un arreglo asociativo por clave normalizada del encabezado.
 *
 * Reutilizado por la carga masiva de postulantes (CU14) y la carga masiva de
 * notas por materia (CU06).
 */
class LectorPlanilla
{
    /**
     * Lee el archivo y devuelve cada fila como arreglo asociativo por clave
     * normalizada del encabezado (minúsculas, sin acentos, con guion bajo).
     *
     * @return array<int,array<string,string>>
     */
    public static function leer(UploadedFile $archivo): array
    {
        $matriz = self::leerMatriz($archivo);
        if (count($matriz) < 2) {
            abort(422, 'El archivo no tiene filas de datos (solo el encabezado).');
        }

        $encabezados = array_map(fn ($h) => self::clave((string) $h), $matriz[0]);

        $filas = [];
        foreach (array_slice($matriz, 1) as $valores) {
            // Salta filas completamente vacías.
            if (count(array_filter($valores, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $fila = [];
            foreach ($encabezados as $idx => $clave) {
                $fila[$clave] = isset($valores[$idx]) ? trim((string) $valores[$idx]) : '';
            }
            $filas[] = $fila;
        }

        return $filas;
    }

    /** Normaliza un encabezado/texto a una clave (minúsculas, sin acentos, guion bajo). */
    public static function clave(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);

        return preg_replace('/[^a-z0-9]+/', '_', $texto) ?: '';
    }

    /**
     * Lee el archivo a una matriz (filas de celdas), aceptando CSV o XLSX real.
     *
     * @return array<int,array<int,string>>
     */
    private static function leerMatriz(UploadedFile $archivo): array
    {
        $ruta = $archivo->getRealPath();
        $contenido = file_get_contents($ruta);
        if ($contenido === false || $contenido === '') {
            abort(422, 'El archivo está vacío o no se pudo leer.');
        }

        // Un XLSX es un ZIP: empieza con la firma "PK".
        if (str_starts_with($contenido, 'PK')) {
            return self::leerXlsx($ruta);
        }

        return self::leerCsv($contenido);
    }

    /** @return array<int,array<int,string>> */
    private static function leerCsv(string $contenido): array
    {
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido); // quita BOM
        $lineas = preg_split('/\r\n|\r|\n/', trim($contenido));
        // Excel en español suele exportar CSV con ';'.
        $sep = substr_count($lineas[0] ?? '', ';') > substr_count($lineas[0] ?? '', ',') ? ';' : ',';

        $matriz = [];
        foreach ($lineas as $linea) {
            if (trim($linea) === '') {
                continue;
            }
            $matriz[] = str_getcsv($linea, $sep);
        }

        return $matriz;
    }

    /**
     * Lee un XLSX real (sin librerías): descomprime el ZIP, resuelve las cadenas
     * compartidas y recorre la primera hoja.
     *
     * @return array<int,array<int,string>>
     */
    private static function leerXlsx(string $ruta): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($ruta) !== true) {
            abort(422, 'No se pudo abrir el archivo Excel. Verifica que sea un .xlsx válido.');
        }

        // Cadenas compartidas (el texto de las celdas vive aquí).
        $compartidas = [];
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if ($ss !== false) {
            $xml = simplexml_load_string($ss);
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    $texto = isset($si->t) ? (string) $si->t : '';
                    if ($texto === '' && isset($si->r)) {
                        foreach ($si->r as $r) {
                            $texto .= (string) $r->t;
                        }
                    }
                    $compartidas[] = $texto;
                }
            }
        }

        // Primera hoja (sheet1, o la primera worksheet disponible).
        $hoja = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($hoja === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $nombre = $zip->getNameIndex($i);
                if ($nombre && preg_match('#^xl/worksheets/.*\.xml$#', $nombre)) {
                    $hoja = $zip->getFromName($nombre);
                    break;
                }
            }
        }
        $zip->close();

        if ($hoja === false || ($xmlHoja = simplexml_load_string($hoja)) === false) {
            abort(422, 'El Excel no tiene una hoja legible.');
        }

        $matriz = [];
        foreach ($xmlHoja->sheetData->row as $row) {
            $fila = [];
            foreach ($row->c as $c) {
                $col = self::columnaIndice((string) $c['r']);
                $tipo = (string) $c['t'];

                if ($tipo === 's') {
                    $valor = $compartidas[(int) $c->v] ?? '';
                } elseif ($tipo === 'inlineStr') {
                    $valor = (string) ($c->is->t ?? '');
                } else {
                    $valor = (string) ($c->v ?? '');
                }

                $fila[$col] = trim($valor);
            }

            if ($fila === []) {
                $matriz[] = [];

                continue;
            }

            // Rellena los huecos para que las columnas queden alineadas.
            $max = max(array_keys($fila));
            $contigua = [];
            for ($i = 0; $i <= $max; $i++) {
                $contigua[] = $fila[$i] ?? '';
            }
            $matriz[] = $contigua;
        }

        return $matriz;
    }

    /** Convierte la referencia de celda (ej. "E2") al índice de columna base 0 (A=0). */
    private static function columnaIndice(string $ref): int
    {
        preg_match('/^[A-Z]+/', strtoupper($ref), $m);
        $letras = $m[0] ?? 'A';

        $idx = 0;
        foreach (str_split($letras) as $ch) {
            $idx = $idx * 26 + (ord($ch) - 64);
        }

        return $idx - 1;
    }
}
