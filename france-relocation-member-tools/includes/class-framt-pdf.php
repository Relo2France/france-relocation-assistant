<?php
/**
 * A small PDF writer for letters and reports.
 *
 * What the old one-page writer could not do, and a letter to a French
 * consulate needs: more than one page, accented characters (é, è, à, ç,
 * ô, the euro sign, typographic quotes) through WinAnsi encoding, and line
 * wrapping measured with Helvetica's real character widths so a paragraph
 * does not run off the right edge. Letter size, one-inch margins, page
 * numbers at the foot. No external libraries.
 *
 * @package     FRA_Member_Tools
 * @subpackage  Documents
 * @since       2.9.25
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FRAMT_PDF {

    const PAGE_W = 612;
    const PAGE_H = 792;
    const MARGIN = 72;

    /** @var array[] Pages, each a list of content-stream lines. */
    private $pages = array();
    /** @var float Current baseline, from the bottom of the page. */
    private $y = 0;
    /** @var string Shown in the document info. */
    private $title;

    /**
     * Helvetica widths (per 1000 units) for printable ASCII 32..126, from
     * the standard AFM. Characters outside this range use the width of
     * their unaccented base letter, which is what Helvetica does.
     *
     * @var int[]
     */
    private static $w = array(
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
        1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
        333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
        556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
    );

    public function __construct( $title = '' ) {
        $this->title = (string) $title;
        $this->new_page();
    }

    private function new_page() {
        $this->pages[] = array();
        $this->y       = self::PAGE_H - self::MARGIN;
    }

    private function ensure_room( $height ) {
        if ( $this->y - $height < self::MARGIN + 18 ) {
            $this->new_page();
        }
    }

    /**
     * UTF-8 to the single-byte WinAnsi the standard fonts use.
     */
    private static function to_winansi( $text ) {
        // Narrow and thin spaces (French number and currency spacing) have
        // no WinAnsi code; a no-break space keeps "2 500 €" on one line.
        $text = str_replace( array( "\u{202F}", "\u{2009}", "\u{2007}" ), "\u{00A0}", (string) $text );
        $text = preg_replace( '/(\d) (€|EUR\b|%)/u', "$1\u{00A0}$2", $text );
        // French quotation marks hold their word: « visiteur » never splits.
        $text = str_replace( array( '« ', ' »' ), array( "«\u{00A0}", "\u{00A0}»" ), $text );
        if ( function_exists( 'mb_convert_encoding' ) ) {
            $converted = @mb_convert_encoding( $text, 'Windows-1252', 'UTF-8' );
            if ( false !== $converted && '' !== $converted ) {
                return $converted;
            }
        }
        if ( function_exists( 'iconv' ) ) {
            $converted = @iconv( 'UTF-8', 'Windows-1252//TRANSLIT', $text );
            if ( false !== $converted ) {
                return $converted;
            }
        }
        return preg_replace( '/[^\x20-\x7E]/', '?', $text );
    }

    /**
     * Width of a WinAnsi string in points.
     */
    private static function width( $bytes, $size, $bold ) {
        $base = array(
            0xE0 => 'a', 0xE1 => 'a', 0xE2 => 'a', 0xE4 => 'a', 0xE7 => 'c', 0xE8 => 'e', 0xE9 => 'e', 0xEA => 'e', 0xEB => 'e',
            0xEE => 'i', 0xEF => 'i', 0xF4 => 'o', 0xF6 => 'o', 0xF9 => 'u', 0xFB => 'u', 0xFC => 'u', 0xC0 => 'A', 0xC7 => 'C',
            0xC9 => 'E', 0xC8 => 'E', 0xCA => 'E', 0xD4 => 'O', 0x92 => "'", 0x91 => "'", 0x93 => '"', 0x94 => '"',
            0x96 => '-', 0x97 => 'M', 0x85 => 'M', 0xA0 => ' ',
        );
        $units = 0;
        $len   = strlen( $bytes );
        for ( $i = 0; $i < $len; $i++ ) {
            $o = ord( $bytes[ $i ] );
            if ( 0x80 === $o ) {
                $units += 800; // the euro sign, with a little air before the digits
            } elseif ( $o >= 32 && $o <= 126 ) {
                $units += self::$w[ $o - 32 ];
            } elseif ( isset( $base[ $o ] ) ) {
                $units += self::$w[ ord( $base[ $o ] ) - 32 ];
            } else {
                $units += 556;
            }
        }
        return $units * $size / 1000 * ( $bold ? 1.06 : 1 );
    }

    private static function escape( $bytes ) {
        return str_replace( array( '\\', '(', ')', "\r" ), array( '\\\\', '\\(', '\\)', '' ), $bytes );
    }

    /**
     * Write wrapped text.
     *
     * @param string $text  UTF-8 text; blank lines are kept as paragraph breaks.
     * @param array  $opt   size, bold, italic, indent, after (space after, pt), center.
     */
    public function text( $text, $opt = array() ) {
        $size   = (float) ( $opt['size'] ?? 11 );
        $bold   = ! empty( $opt['bold'] );
        $italic = ! empty( $opt['italic'] );
        $indent = (float) ( $opt['indent'] ?? 0 );
        $lead   = $size * 1.38;
        $font   = $bold ? 'F2' : ( $italic ? 'F3' : 'F1' );
        $max    = self::PAGE_W - 2 * self::MARGIN - $indent;

        foreach ( preg_split( "/\r?\n/", (string) $text ) as $para ) {
            if ( '' === trim( $para ) ) {
                $this->y -= $lead * 0.6;
                continue;
            }
            $words = preg_split( '/ +/', self::to_winansi( $para ) );
            $line  = '';
            foreach ( $words as $word ) {
                $try = '' === $line ? $word : $line . ' ' . $word;
                if ( '' !== $line && self::width( $try, $size, $bold ) > $max ) {
                    $this->line( $line, $size, $font, $indent, $lead, ! empty( $opt['center'] ), $bold );
                    $line = $word;
                } else {
                    $line = $try;
                }
            }
            if ( '' !== $line ) {
                $this->line( $line, $size, $font, $indent, $lead, ! empty( $opt['center'] ), $bold );
            }
        }
        $this->y -= (float) ( $opt['after'] ?? 0 );
    }

    private function line( $bytes, $size, $font, $indent, $lead, $center, $bold ) {
        $this->ensure_room( $lead );
        $x = self::MARGIN + $indent;
        if ( $center ) {
            $x = ( self::PAGE_W - self::width( $bytes, $size, $bold ) ) / 2;
        }
        $this->y -= $size;
        // The euro sign sits in the code range some viewers give the wrong
        // advance to, so the text after it would overlap it. Each run after
        // a euro sign is placed at its measured position instead.
        $runs = preg_split( '/(\x80)/', $bytes, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
        foreach ( $runs as $run ) {
            $this->pages[ count( $this->pages ) - 1 ][] = sprintf( 'BT /%s %.1F Tf %.2F %.2F Td (%s) Tj ET', $font, $size, $x, $this->y, self::escape( $run ) );
            $x += self::width( $run, $size, $bold );
        }
        $this->y -= ( $lead - $size );
    }

    public function heading( $text ) {
        $this->ensure_room( 40 );
        $this->text( $text, array( 'size' => 15, 'bold' => true, 'after' => 6 ) );
    }

    public function subheading( $text ) {
        $this->ensure_room( 30 );
        $this->space( 4 );
        $this->text( $text, array( 'size' => 11.5, 'bold' => true, 'after' => 2 ) );
    }

    public function paragraph( $text ) {
        $this->text( $text, array( 'after' => 6 ) );
    }

    public function note( $text ) {
        $this->text( $text, array( 'size' => 9, 'italic' => true, 'after' => 4 ) );
    }

    public function bullets( $items ) {
        foreach ( (array) $items as $item ) {
            $this->ensure_room( 16 );
            $this->pages[ count( $this->pages ) - 1 ][] = sprintf( 'BT /F1 11 Tf %.2F %.2F Td (%s) Tj ET', self::MARGIN + 4, $this->y - 11, self::escape( self::to_winansi( "\xE2\x80\xA2" ) ) );
            $this->text( $item, array( 'indent' => 16, 'after' => 2 ) );
        }
        $this->space( 4 );
    }

    public function space( $pt = 10 ) {
        $this->y -= $pt;
    }

    public function rule() {
        $this->ensure_room( 12 );
        $this->y -= 6;
        $this->pages[ count( $this->pages ) - 1 ][] = sprintf( '0.8 0.83 0.81 RG 0.5 w %d %.2F m %d %.2F l S', self::MARGIN, $this->y, self::PAGE_W - self::MARGIN, $this->y );
        $this->y -= 8;
    }

    /**
     * A labelled line to sign or fill in by hand.
     */
    public function sign_line( $label ) {
        $this->ensure_room( 40 );
        $this->space( 22 );
        $this->pages[ count( $this->pages ) - 1 ][] = sprintf( '0.2 0.2 0.2 RG 0.6 w %d %.2F m %d %.2F l S', self::MARGIN, $this->y, self::MARGIN + 240, $this->y );
        $this->space( 4 );
        $this->text( $label, array( 'size' => 9 ) );
    }

    /**
     * The finished PDF as a string.
     */
    public function output() {
        $objects = array();
        $total   = count( $this->pages );

        // 1 catalog, 2 pages, 3-5 fonts, then per page: page object + content.
        $fonts = array(
            3 => 'Helvetica',
            4 => 'Helvetica-Bold',
            5 => 'Helvetica-Oblique',
        );
        $kids = array();
        $next = 6;
        $page_objs = array();
        foreach ( $this->pages as $i => $lines ) {
            $footer   = sprintf( 'BT /F1 8 Tf %d %d Td (%s) Tj ET', self::MARGIN, 40, self::escape( self::to_winansi( ( '' !== $this->title ? $this->title . '  ·  ' : '' ) . 'Page ' . ( $i + 1 ) . ' of ' . $total ) ) );
            $stream   = "0.11 0.14 0.13 rg\n" . implode( "\n", $lines ) . "\n0.37 0.43 0.40 rg\n" . $footer;
            $page_id  = $next++;
            $cont_id  = $next++;
            $kids[]   = $page_id . ' 0 R';
            $page_objs[ $page_id ] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_W . ' ' . self::PAGE_H . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R /F3 5 0 R >> >> /Contents ' . $cont_id . ' 0 R >>';
            $page_objs[ $cont_id ] = '<< /Length ' . strlen( $stream ) . " >>\nstream\n" . $stream . "\nendstream";
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . '] /Count ' . $total . ' >>';
        foreach ( $fonts as $id => $name ) {
            $objects[ $id ] = '<< /Type /Font /Subtype /Type1 /BaseFont /' . $name . ' /Encoding /WinAnsiEncoding >>';
        }
        $objects += $page_objs;
        ksort( $objects );

        $info_id = $next;
        $objects[ $info_id ] = '<< /Title (' . self::escape( self::to_winansi( $this->title ) ) . ') /Producer (Relo2France) >>';

        $out     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = array();
        foreach ( $objects as $id => $body ) {
            $offsets[ $id ] = strlen( $out );
            $out           .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen( $out );
        $max  = max( array_keys( $objects ) );
        $out .= "xref\n0 " . ( $max + 1 ) . "\n0000000000 65535 f \n";
        for ( $i = 1; $i <= $max; $i++ ) {
            $out .= isset( $offsets[ $i ] ) ? sprintf( "%010d 00000 n \n", $offsets[ $i ] ) : "0000000000 65535 f \n";
        }
        $out .= "trailer\n<< /Size " . ( $max + 1 ) . ' /Root 1 0 R /Info ' . $info_id . " 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return $out;
    }

    // ------------------------------------------------------------------
    // The old one-page writer's calls, so existing report code can switch
    // to this writer without being rewritten.
    // ------------------------------------------------------------------

    public function addPage() {} // phpcs:ignore -- pages are added as text fills them.

    public function writeTitle( $text ) { // phpcs:ignore
        $this->heading( $text );
    }

    /**
     * Leading spaces indent (two per level); a leading "- " is a bullet.
     */
    public function write( $text, $bold = false ) {
        $text   = (string) $text;
        $trim   = ltrim( $text, ' ' );
        $indent = min( 3, (int) floor( ( strlen( $text ) - strlen( $trim ) ) / 2 ) ) * 12;
        if ( 0 === strpos( $trim, '- ' ) ) {
            $this->ensure_room( 16 );
            $this->pages[ count( $this->pages ) - 1 ][] = sprintf( 'BT /F1 10.5 Tf %.2F %.2F Td (%s) Tj ET', self::MARGIN + $indent + 4, $this->y - 10.5, self::escape( self::to_winansi( "\xE2\x80\xA2" ) ) );
            $this->text( substr( $trim, 2 ), array( 'size' => 10.5, 'indent' => $indent + 16, 'after' => 1 ) );
            return;
        }
        $this->text( $trim, array( 'size' => $bold ? 11.5 : 10.5, 'bold' => $bold, 'indent' => $indent, 'after' => $bold ? 2 : 1 ) );
    }

    public function addSpace( $lines = 1 ) { // phpcs:ignore
        $this->space( 12 * (float) $lines );
    }

    public function save( $path ) {
        return false !== file_put_contents( $path, $this->output() );
    }
}
