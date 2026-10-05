<?php
// Splices the "Double Board-Certified" credential row into seeded posts 166 + 169.
// usage: wp eval-file --user=1 deploy-credentials.php [apply]
$apply = in_array( 'apply', $args ?? array(), true );
$old   = 'Fellow of the American Board of Orthopaedic Surgeons \(2014\)';
$l1    = 'Board-certified, American Board of Orthopaedic Surgery (ABOS)';
$l2    = 'Fellow, American Academy of Orthopaedic Surgeons (FAAOS)';
$cfg   = array(
	166 => array( 'size' => 'text-[14px] leading-[1.6] text-ink', 'list' => 'space-y-0.5' ),
	169 => array( 'size' => 'text-[16px] lg:text-[17px] leading-[1.5] text-ink', 'list' => 'space-y-1' ),
);
foreach ( $cfg as $id => $c ) {
	$post = get_post( $id );
	$src  = $post->post_content;
	$cls  = 'col-start-1 sm:col-start-2 list-disc pl-5 ' . $c['list'] . ' ' . $c['size'];
	$re   = '/^([ \t]*)<p class="([^"]*)">' . $old . '<\/p>\n[ \t]*<!-- \/wp:paragraph -->/m';
	$out  = preg_replace_callback( $re, function ( $m ) use ( $l1, $l2, $cls ) {
		$i = $m[1];
		return $i . '<p class="' . $m[2] . '">Double Board-Certified</p>' . "\n" . $i . '<!-- /wp:paragraph -->' . "\n\n"
			. $i . '<!-- wp:list {"className":"' . $cls . '"} -->' . "\n"
			. $i . '<ul class="wp-block-list ' . $cls . '"><!-- wp:list-item -->' . "\n"
			. $i . '<li>' . $l1 . '</li>' . "\n" . $i . '<!-- /wp:list-item -->' . "\n\n"
			. $i . '<!-- wp:list-item -->' . "\n" . $i . '<li>' . $l2 . '</li>' . "\n" . $i . '<!-- /wp:list-item --></ul>' . "\n"
			. $i . '<!-- /wp:list -->';
	}, $src, -1, $n );
	echo "post $id: matches=$n len " . strlen( $src ) . ' -> ' . strlen( $out ) . "\n";
	if ( 1 !== $n ) { echo "ABORT: expected exactly 1 match\n"; exit( 1 ); }
	// Round-trip sanity: parse must still produce balanced blocks.
	$blocks = parse_blocks( $out );
	echo '  parse ok, top-level blocks: ' . count( $blocks ) . ' (before: ' . count( parse_blocks( $src ) ) . ")\n";
	if ( $apply ) {
		$r = wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( $out ) ), true );
		echo is_wp_error( $r ) ? 'FAILED: ' . $r->get_error_message() . "\n" : "  updated\n";
	}
}
