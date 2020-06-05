<?php
/**
 * Generic Syntax Highlighting with Regular Expressions
 * 
 * @category Core Class
 * @author Dominic Szablewski, @phoboslab on Thursday, August 2nd 2007
 * @see https://phoboslab.org/log/2007/08/generic-syntax-highlighting-with-regular-expressions
 * 
 */
class SyntaxHighlight
{

    public static function process( $s ) {
        $s = htmlspecialchars( $s );

        // Workaround for escaped backslashes
        $s = str_replace( '\\\\','\\\\<e>', $s ); 

        $regexp = array(
            // Comments/Strings
            '/(
                \/\*.*?\*\/|
                \/\/.*?\n|
                \#.*?\n|
                (?<!\\\)&quot;.*?(?<!\\\)&quot;|
                (?<!\\\)\'(.*?)(?<!\\\)\'
            )/isex' 
            => 'self::replaceId($tokens,\'$1\')',

            // Numbers (also look for Hex)
            '/(?<!\w)(
                0x[\da-f]+|
                \d+
            )(?!\w)/ix'
            => '<span class="N">$1</span>',

            // Make the bold assumption that an all uppercase word has a 
            // special meaning
            '/(?<!\w|>)(
                [A-Z_0-9]{2,}
            )(?!\w)/x'
            => '<span class="D">$1</span>', 

            // Keywords
            '/(?<!\w|\$|\%|\@|>)(
                and|or|xor|for|do|while|foreach|as|return|die|exit|if|then|else|
                elseif|new|delete|try|throw|catch|finally|class|function|string|
                array|object|resource|var|bool|boolean|int|integer|float|double|
                real|string|array|global|const|static|public|private|protected|
                published|extends|switch|true|false|null|void|this|self|struct|
                char|signed|unsigned|short|long
            )(?!\w|=")/ix'
            => '<span class="K">$1</span>', 

            // PHP/Perl-Style Vars: $var, %var, @var
            '/(?<!\w)(
                (\$|\%|\@)(\-&gt;|\w)+
            )(?!\w)/ix'
            => '<span class="V">$1</span>'
        );

        $tokens = array(); // This array will be filled from the regexp-callback
        $s = preg_replace( array_keys($regexp), array_values($regexp), $s );

        // Paste the comments and strings back in again
        $s = str_replace( array_keys($tokens), array_values($tokens), $s );

        // Delete the "Escaped Backslash Workaround Token" (TM) and replace 
        // tabs with four spaces.
        $s = str_replace( array( '<e>', "\t" ), array( '', '    ' ), $s );

        return '<pre>'.$s.'</pre>';
    }

    // Regexp-Callback to replace every comment or string with a uniqid and save 
    // the matched text in an array
    // This way, strings and comments will be stripped out and wont be processed 
    // by the other expressions searching for keywords etc.
    private static function replaceId( &$a, $match ) {
        $id = "##r".uniqid()."##";

        // String or Comment?
        if( $match{0} == '/' || $match{0} == '#' ) {
            $a[$id] = '<span class="C">'.$match.'</span>';
        } else {
            $a[$id] = '<span class="S">'.$match.'</span>';
        }
        return $id;
    }
}