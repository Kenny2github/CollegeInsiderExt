<?php

class CollegeInsiderHooks {
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'article', [ self::class, 'renderArticle' ], SFH_OBJECT_ARGS );
	}

	public static function renderArticle( Parser $parser, PPFrame $frame, $args ) {
		$type = $frame->expand($args[0]);
		$index = intval($frame->expand($args[1]));
		return "(Would get the $index-th $type here)"; // TODO
	}
}