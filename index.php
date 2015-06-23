<?php

require_once __DIR__ . '/autoload.php';

use NeuroSys\FileMerger\Merger;
use NeuroSys\FileMerger\Driver\PdfTkDriver;
use NeuroSys\FileMerger\Transformer\ImageTransformer;
use Knp\Snappy\Pdf;

$handle = fopen("php://stdin","r");
echo "Path to markdown files (upto folder without trailing slash '/' ):" . PHP_EOL;
$dir = rtrim(fgets($handle, 1024));
fclose($handle);

$files = scandir($dir);
$pdfs = array();
$domain_name = 'http://fabien.potencier.org';

$index = 1;
foreach ($files as $file){
	if (!in_array($file, array('.', '..'))) { // as scandir() returns file list with dots which actually specifies parent directory
		// md to html
		$markdown = file_get_contents( $dir.'/'.$file );
		$markdownParser = new \Michelf\MarkdownExtra();
		$html = $markdownParser->transform( $markdown );


		// html clean up and clean up links by assigning absolute path to them
		$dom = \HTML5::loadHTML( $html );
		$links = htmlqp( $dom, 'a' );
		foreach ( $links as $link ) {
			$href = $link->attr( 'href' );
			if ( substr( $href, 0, 1 ) == '/' && substr( $href, 1, 1 ) != '/' ) {
				$link->attr( 'href', $domain_name . $href );
			}
		}
		$html = \HTML5::saveHTML( $dom );


		// html to pdf
		$snappy = new Pdf( '/usr/bin/wkhtmltopdf' );
		$snappy->generateFromHtml( $html, $dir . '/pdf/' . $index . '.pdf' );
		$pdfs[$index] = $dir . '/pdf/' . $index . '.pdf';

		$index ++;
	}
}

// merging all files
$driver = new PdfTkDriver("/usr/bin/pdftk");
$merger = new Merger($driver);
$merger->addTransformer(new ImageTransformer($snappy));

foreach ($pdfs as $pdf) {
	$merger->addFile($pdf);
}

$merger->merge($dir . '/pdf/CreateYourOwnFrameWork-FabPot.pdf');
echo PHP_EOL;
echo "Combined e-book is: " . $dir . '/pdf/CreateYourOwnFrameWork-FabPot.pdf';