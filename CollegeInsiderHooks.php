<?php
require_once __DIR__ . '/defines.php';

class CollegeInsiderHooks {
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'article', [ self::class, 'renderArticle' ], SFH_OBJECT_ARGS );
	}

	public static function renderArticle( Parser $parser, PPFrame $frame, $args ) {
		$dbr = wfGetDB( DB_REPLICA );
		$index = intval( $frame->expand( $args[0] ) );
		$type = isset( $args[1] ) ? $frame->expand( $args[1] ) : '*';
		$cls = isset( $args[2] ) ? $frame->expand( $args[2] ) : 'news';
		if ( !$type || $type == '*' ) {
			$conds = [];
		} else {
			$conds = [ 'cl_to' => str_replace( ' ', '_', wfMessage(
				"collegeinsider-type-$type"
			)->text() ) . 's' ];
		}
		$row = $dbr->selectRow(
			[ 'ci' => 'collegeinsider', 'cl' => 'categorylinks' ],
			[ 'page_id', 'thumbnail', 'blurb' ],
			$conds, __METHOD__,
			[ 'ORDER BY' => 'datestamp DESC', 'OFFSET' => $index ],
			[ 'cl' => [ 'INNER JOIN', 'page_id=cl_from' ] ]
		);
		if ( !$row ) return wfMessage(
			'collegeinsider-invalid-article',
			$index, $type
		)->text();
		$file = wfFindFile( $row->thumbnail );
		if ( !$file ) return $row->thumbnail;
		$file = $file->getUrl();
		$description = htmlspecialchars( $row->blurb );
		$page = WikiPage::newFromID( $row->page_id );
		$pageTitle = $page->getTitle();
		$h3 = $pageTitle->getText();
		$link = $pageTitle->getLocalURL();
		$hashtags = [];
		foreach ( $page->getCategories() as $title ) {
			$key = COLLEGEINSIDER_CATEGORIES[$title->getText()];
			if ( !$key ) continue;
			$url = $title->getLocalURL();
			$hashtags[] = "<a href=\"$url\">#positive$key</a>";
		}
		$hashtags = implode( '', $hashtags ) ?: '<a>#positiveeducation</a>';
		return [ <<<EOS
<div class="$cls" style="background-image: url('$file');">
	<div class="hashtags">&nbsp;$hashtags</div>
	<a href="$link"><div class="description">
		<h3>$h3</h3>
		<p class="long-description">$description</p>
	</div></a>
</div>
EOS, 'noparse' => true, 'isHTML' => true ];
	}

	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionTable(
			'collegeinsider',
			__DIR__ . '/sql/table.sql'
		);
		return true;
	}
}