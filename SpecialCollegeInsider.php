<?php

class SpecialCollegeInsider extends SpecialPage {
	function __construct() {
		parent::__construct( 'CollegeInsider', 'edit' );
	}

	function execute( $par ) {
		global $wgCollegeInsiderCategories, $wgCollegeInsiderTypes;
		$request = $this->getRequest();
		$out = $this->getOutput();
		$this->setHeaders();
		$out->setPageTitle( wfMessage( 'collegeinsider-edit-title' )->escaped() );

		if ( $request->wasPosted() ) {
			$this->doPost( $par, $request, $out );
			return;
		}

		$pageid = intval( $par );
		$page = $pageid ? WikiPage::newFromID( $pageid ) : false;
		$metaRow = $page ? wfGetDB( DB_REPLICA )->selectRow(
			'collegeinsider', '*', [ 'page_id' => $page->getID() ]
		) : false;

		$out->addHTML( Html::openElement( 'form', [
			'id' => 'editform',
			'method' => 'POST'
		] ) );

		// Type of article
		$typeOpts = [];
		foreach ( $wgCollegeInsiderTypes as $type ) {
			$typeOpts[] = Html::element(
				'option', [ 'value' => $type ],
				wfMessage( "collegeinsider-type-$type" )->text()
			);
		}
		$out->addHTML( Html::rawElement( 'p', [], Html::label(
			wfMessage( 'collegeinsider-type-label' )->text(),
			'collegeinsider-type-input'
		) . ' ' . Html::rawElement( 'select', [
			'id' => 'collegeinsider-type-input',
			'name' => 'type'
		], implode( '', $typeOpts ) ) ) );

		// Article datestamp
		$out->addHTML( Html::rawElement( 'p', [], Html::label(
			wfMessage( 'collegeinsider-date-label' )->text(),
			'collegeinsider-date-input'
		) . ' ' . Html::input(
			'date', date(
				'Y-m-d',
				$metaRow
				? wfTimestamp( TS_UNIX, $metaRow->datestamp . '000000' )
				: time()
			), 'date', [ 'id' => 'collegeinsider-date-input', 'required' => '' ]
		) ) );

		// Article title
		$out->addHTML( Html::element( 'h2', [
			'id' => 'articletitle',
			'contenteditable' => 'true'
		], $page ? $page->getTitle()->getText()
		: wfMessage( 'collegeinsider-default-title' )->text() ) );

		// Categories (formerly hashtags)
		$pageCats = [];
		if ( $page ) {
			foreach ( $page->getCategories() as $cat ) {
				$pageCats[] = $wgCollegeInsiderCategories[$cat->getText()];
			}
		}
		$labels = [];
		foreach ( $wgCollegeInsiderCategories as $cat => $tag ) {
			$labels[] = Html::rawElement( 'label', [], Html::input(
				'tags[]', $tag, 'checkbox',
				in_array( $tag, $pageCats ) ? [ 'checked' => '' ] : []
			) . Html::element( 'a', [], $cat ) );
		}
		$out->addHTML( Html::rawElement(
			'p', [],
			wfMessage( 'collegeinsider-category-label' )->escaped()
			. implode( '', $labels )
		) );

		// Byline
		$this->addLabelHeader( $out, 'byline' );
		$out->addHTML( Html::element( 'p', [
			'id' => 'byline',
			'contenteditable' => 'true'
		], $metaRow ? $metaRow->byline
		: wfMessage( 'collegeinsider-default-byline' )->text() ) );

		// Description
		$this->addLabelHeader( $out, 'description' );
		$out->addHTML( Html::element( 'div', [
			'id' => 'description',
			'contenteditable' => 'true'
		], $metaRow ? $metaRow->blurb : '' ) );

		// Content
		$this->addLabelHeader( $out, 'content' );
		$out->addHTML( Html::rawElement( 'div', [
			'id' => 'editfield',
			'contenteditable' => 'true'
		], $page ? self::fulltextFromPage( $page ) : '' ) );

		// Language
		$out->addHTML( Html::rawElement( 'p', [], Html::label(
			wfMessage( 'collegeinsider-language-label' )->text(),
			'collegeinsider-language-input'
		) . ' ' . Html::rawElement( 'select', [
			'id' => 'collegeinsider-language-input',
			'name' => 'lang'
		], Html::element(
			'option', [ 'value' => 'en' ], // default is en anyway
			wfMessage( 'collegeinsider-lang-en' )->text()
		) . Html::element(
			'option',
			($metaRow && $metaRow->lang == 'zh')
			? [ 'value' => 'zh', 'selected' => '' ]
			: [ 'value' => 'zh' ],
			wfMessage( 'collegeinsider-lang-zh' )->text()
		) ) ) );

		// Background image
		$out->addHTML( Html::rawElement( 'p', [], Html::input(
			'thumbnail', ($metaRow ? $metaRow->thumbnail : ''), 'text',
			[
				'autocomplete' => 'off',
				'placeholder' => wfMessage( 'collegeinsider-search-placeholder' )->escaped(),
				'style' => 'width: min(250px, 100%)'
			]
		) ) );

		// Submit button
		$out->addHTML( Html::rawElement( 'p', [], Html::input(
			null, wfMessage( 'collegeinsider-save' )->escaped(), 'submit'
		) ) );

		// contenteditable trickery
		foreach ( [ 'articletitle', 'byline', 'description', 'fulltext' ] as $key ) {
			$out->addHTML( Html::hidden(
				$key, '', [ 'id' => "real$key", 'required' => '' ]
			) );
		}

		$out->addHTML( Html::inlineScript(
<<<'EOS'
		e = (id) => document.getElementById(id);
		e('editform').onsubmit = function () {
			e('realarticletitle').value = e('articletitle').innerText;
			e('realbyline').value = e('byline').innerText;
			e('realdescription').value = e('description').innerHTML;
			e('realfulltext').value = e('editfield').innerHTML;
			return true;
		};
		function rid(i) {i.remove()};
		window.addEventListener('paste', () => setTimeout(() => {
			document.querySelectorAll('[contenteditable=true] script').forEach(rid);
		}, 0));
		function htmlspecialchars(el) {
			el.innerHTML = el.innerText.trim()
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		};
		e('articletitle').addEventListener('paste', () => setTimeout(() => {
			htmlspecialchars(e('articletitle'));
		}, 0));
		e('description').addEventListener('paste', () => setTimeout(() => {
			htmlspecialchars(e('description'));
		}, 0));
		e('byline').addEventListener('paste', () => setTimeout(() => {
			htmlspecialchars(e('byline'));
		}, 0));
		e('editfield').addEventListener('paste', () => setTimeout(() => {
			function conform(i) {
				var inText = i.innerHTML;
				if (i.style.fontStyle == 'italic') {
					inText = '<i>' + inText + '</i>';
				}
				if (i.style.fontWeight) {
					inText = '<b>' + inText + '</b>';
				}
				if (i.style.textDecorationLine) {
					inText = '<u>' + inText + '</u>';
				}
				if (i.style.verticalAlign == 'super') {
					inText = '<sup>' + inText + '</sup>';
				}
				if (i.style.verticalAlign == 'sub') {
					inText = '<sub>' + inText + '</sub>';
				}
				i.outerHTML = inText;
			}
			document.querySelectorAll('#editfield span:not([id]) span:not([id])').forEach(conform);
			document.querySelectorAll('#editfield span:not([id])').forEach(conform);
			document.querySelectorAll('#editfield p').forEach(function (i) {
				i.removeAttribute('dir');
				i.removeAttribute('style');
				i.outerHTML = i.outerHTML.replace(/( |&nbsp;)\n*<\//g, '</');
			});
			document.querySelectorAll('#editfield br').forEach(rid);
			document.querySelectorAll('#editfield p').forEach((i) => {
				if (!i.innerHTML) i.remove();
			});
			document.querySelectorAll('#editfield span[id]').forEach(function (i) {
				i.outerHTML = i.innerHTML;
			});
		}, 0));
EOS
		) );

		$out->addHTML( Html::closeElement( 'form' ) );
	}

	private function addLabelHeader( OutputPage $out, $key ) {
		$out->addHTML( Html::element(
			'h4', [], wfMessage( "collegeinsider-$key-label" )->text()
		) );
	}

	public static function fulltextFromPage( Wikipage $page ) {
		$content = $page->getContent();
		$title = $page->getTitle();
		$re1 = '%^.*\n+|\n*\[\[[Cc]ategory:[^\]]+\]\]\n*%';
		$re2 = '%<div class="mw-parser-output">(.*)</div>%s';
		$re3 = '%\n*<!--.*-->\n*%s';
		$text = $content->getNativeData();
		$newContent = new WikitextContent( preg_replace( $re1, '', $text ) );
		$html = $newContent->getParserOutput( $title )->getText();
		$html = preg_replace( $re2, '$1', $html );
		return preg_replace( $re3, '', $html );
	}

	public static function fulltextToWikitext( $text ) {
		$re1 = '%</?tbody>%';
		$text = preg_replace( $re1, '', $text );
		return $text;
	}

	public function doPost( $par, WebRequest $request, OutputPage $out ) {
		global $wgCollegeInsiderCategories;
		$user = $this->getUser();

		$thumbnail = Title::newFromText( 'File:' . $request->getText( 'thumbnail' ) );
		if ( !$thumbnail->getArticleID() ) {
			$out->addWikiMsg(
				'collegeinsider-no-such-file',
				$request->getText( 'thumbnail' )
			);
			$out->addReturnTo( $this->getPageTitle( $par ) );
			return;
		}

		$page = WikiPage::newFromID( intval( $par ) );
		$userTitle = Title::newFromText( $request->getText( 'articletitle' ) );
		if ( !$page ) {
			// since $par is not a valid article ID, attempt to get the page
			// object from the title given by the user.
			// newFromID(getID()) will return null if the title does not
			// represent an EXISTING page.
			$page = WikiPage::newFromID( $userTitle->getArticleID() );
			if ( !$page ) {
				// no existing page with this title, so we are creating a new page
				$page = WikiPage::factory( $userTitle );
			}
		} else {
			// $par is a valid article ID, so a different title means we're moving
			$title = $page->getTitle();
			if ( !$title->equals( $userTitle ) ) {
				$move = new MovePage( $title, $userTitle );
				$move->move(
					$user,
					wfMessage( 'collegeinsider-move-reason' )->text(),
					false
				);
				$page = WikiPage::newFromID( $page->getId() ); // update title
				$out->addWikiMsg(
					'collegeinsider-moved',
					$title->getPrefixedText(),
					$userTitle->getPrefixedText()
				);
			}
		}

		$date = intval( str_replace( '-', '', $request->getVal( 'date' ) ) );
		$categories = [];
		foreach ( $request->getArray( 'tags' ) as $tag) {
			$cat = array_flip( $wgCollegeInsiderCategories )[$tag];
			$categories[] = "[[Category:$cat]]";
		}
		$articleType = $request->getText( 'type' );
		$articleType = wfMessage( "collegeinsider-type-$articleType" )->text() . 's';
		$categories[] = "[[Category:$articleType]]";
		$categories = implode( '', $categories );
		$byline = $request->getText( 'byline' );
		$description = $request->getText( 'description' );
		$content = self::fulltextToWikitext( $request->getText( 'fulltext' ) );
		$language = $request->getVal( 'lang' );
		$thumbnail = $thumbnail->getText();

		$content = $byline . "\n\n" . $content . "\n\n" . $categories;
		$page->doEditContent(
			new WikitextContent( $content ),
			wfMessage( 'collegeinsider-edit-reason' )->text(),
			EDIT_INTERNAL, false, $user
		);

		wfGetDB( DB_MASTER )->replace(
			'collegeinsider',
			'page_id',
			[
				'page_id' => $page->getID(),
				'thumbnail' => $thumbnail,
				'datestamp' => $date,
				'byline' => $byline,
				'blurb' => $description,
				'lang' => $language
			]
		);

		$out->addWikiMsg( 'collegeinsider-saved', $userTitle->getPrefixedText() );
		$out->addReturnTo(
			$this->getPageTitle( $par ?: $page->getID() ),
			$request->getQueryValues(),
			$this->getPageTitle()->getPrefixedText()
		);
	}
}
