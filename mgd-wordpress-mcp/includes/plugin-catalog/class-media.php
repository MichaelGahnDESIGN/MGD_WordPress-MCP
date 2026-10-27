<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MGD_Catalog_V4_Media' ) ) {
    // Icons + Hintergründe aus dem Feed (theme.michael-gahn.de) lokal cachen > uploads/mgd-plugin-catalog
    // Nur Server > Server, im Admin lädt der Browser nie was von extern (DSGVO)
    final class MGD_Catalog_V4_Media {
        private const URL_PATTERN = '~^https://theme\.michael-gahn\.de/uploads/catalog/([a-f0-9]{32}\.webp)$~D';
        private const FILE_PATTERN = '/^[a-f0-9]{32}\.webp$/D';
        private const DIR = 'mgd-plugin-catalog';
        private const MAX_BYTES = 614400;
        private const MAX_SIDE = 2000;
        private const MAX_FAILED = 2;
        private const KEEP_DAYS = 14;

        // Dateiname aus der Feed-URL, sonst leer (nur https + eigener Host + Platform-Dateiname)
        public static function name_from_url( $url ): string {
            return is_string( $url ) && 1 === preg_match( self::URL_PATTERN, $url, $match ) ? $match[1] : '';
        }

        // Akzentfarbe nur als #rrggbb
        public static function accent( $value ): string {
            return is_string( $value ) && 1 === preg_match( '/^#[0-9a-fA-F]{6}$/D', $value ) ? $value : '';
        }

        // Bild holen falls noch nicht da. Gibt den Dateinamen zurück, bei Fehler leer (dann greift das mitgelieferte Motiv)
        public static function sync( $url, int &$failed ): string {
            $name = self::name_from_url( $url );
            $dir = self::dirs()[0];
            if ( '' === $name || '' === $dir ) { return ''; }
            $target = $dir . '/' . $name;
            if ( is_file( $target ) ) { return $name; }
            // zu viele Fehler in einem Lauf > nicht weiter probieren
            if ( $failed >= self::MAX_FAILED ) { return ''; }
            $body = self::download( $url );
            if ( '' === $body || ! wp_mkdir_p( $dir ) ) { ++$failed; return ''; }
            self::guard_index( $dir );
            $tmp = $target . '.tmp';
            if ( false === file_put_contents( $tmp, $body, LOCK_EX ) ) { ++$failed; return ''; }
            if ( ! rename( $tmp, $target ) ) { @unlink( $tmp ); ++$failed; return ''; }
            return $name;
        }

        // Lokale URL, nur wenn die Datei wirklich da ist
        public static function url( $name ): string {
            if ( ! is_string( $name ) || 1 !== preg_match( self::FILE_PATTERN, $name ) ) { return ''; }
            [ $dir, $base ] = self::dirs();
            return '' !== $dir && is_file( $dir . '/' . $name ) ? $base . '/' . $name : '';
        }

        // Alte Dateien weg, die nicht mehr im Feed stehen (erst nach ein paar Tagen)
        public static function prune( array $keep ): void {
            $dir = self::dirs()[0];
            if ( '' === $dir || ! is_dir( $dir ) ) { return; }
            foreach ( (array) glob( $dir . '/*.webp' ) as $file ) {
                if ( in_array( basename( $file ), $keep, true ) ) { continue; }
                if ( filemtime( $file ) < time() - self::KEEP_DAYS * DAY_IN_SECONDS ) { @unlink( $file ); }
            }
        }

        private static function download( string $url ): string {
            $response = wp_remote_get( $url, array( 'timeout' => 4, 'redirection' => 0, 'limit_response_size' => self::MAX_BYTES + 1, 'headers' => array( 'Accept' => 'image/webp' ) ) );
            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return ''; }
            $body = wp_remote_retrieve_body( $response );
            return self::is_webp( $body ) ? $body : '';
        }

        // Echtes WebP? Magic Bytes, RIFF-Länge, Größe, Maße
        private static function is_webp( $body ): bool {
            if ( ! is_string( $body ) || strlen( $body ) < 30 || strlen( $body ) > self::MAX_BYTES ) { return false; }
            if ( 'RIFF' !== substr( $body, 0, 4 ) || 'WEBP' !== substr( $body, 8, 4 ) ) { return false; }
            if ( strlen( $body ) - 8 !== unpack( 'V', substr( $body, 4, 4 ) )[1] ) { return false; }
            $info = @getimagesizefromstring( $body );
            return is_array( $info ) && 'image/webp' === ( $info['mime'] ?? '' ) && $info[0] > 0 && $info[1] > 0 && $info[0] <= self::MAX_SIDE && $info[1] <= self::MAX_SIDE;
        }

        // [Ordner, URL] im Uploads-Verzeichnis, leer wenn WordPress keins hat
        private static function dirs(): array {
            $upload = wp_upload_dir( null, false );
            if ( ! empty( $upload['error'] ) || empty( $upload['basedir'] ) || empty( $upload['baseurl'] ) ) { return array( '', '' ); }
            return array( rtrim( $upload['basedir'], '/' ) . '/' . self::DIR, rtrim( $upload['baseurl'], '/' ) . '/' . self::DIR );
        }

        // Leere index.php gegen Verzeichnislisting
        private static function guard_index( string $dir ): void {
            if ( ! is_file( $dir . '/index.php' ) ) { @file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); }
        }
    }
}
