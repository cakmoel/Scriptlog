<?php
/**
 * remove_accents
 * Converts all accent characters to their ASCII equivalents
 *
 * @category function
 * @author Nirmalakhanza <nirmala.adiba.khanza@gmail.com>
 * @uses Util::remove_accents remove_accent
 * @param string $string
 * @return void
 * 
 */
function remove_accents($string)
{
    
 if (class_exists('Transliterator')) {

    $transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;', Transliterator::FORWARD);
    
    return $transliterator->transliterate($string);

 } else {

    return class_exists('Util') ? Util::remove_accents($string) : "";
 }
}