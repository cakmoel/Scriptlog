<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class Taxonomy 
 * 
 * @category Core Class
 * 
 */
class Taxonomy
{

public static $item = [];

public static function appendItem($id, $parent, $liAttr, $label)
{
   self::$item[$parent][] = ['ID' => $id,   'liAttr' => $liAttr, 'label' => $label];
}

public static function createListItem($ulAttr = '', $ulVisibility = true)
{
 return self::recursiveListItem(0, $ulAttr, $ulVisibility);
}

public static function recursiveListItem($parent, $ulAttr = '', $attrs = '', $idAttr = '')
{

$html = null;

static $t = 1;

$indent = str_repeat("\t\t", $t);

if (isset(self::$item[$parent])) {

    if ($ulAttr) {

        $ulAttr = $ulAttr;

    }

    if ($attrs) {

        $attrs = $attrs;

    }

    if ($idAttr) {

        $idAttr = $idAttr;

    }

    $html = "\n$indent";

	$html .= "<ul ".$ulAttr.">";

    $t++;

    foreach (self::$item[$parent] as $i) {
        
        $child = self::recursiveListItem($i['ID'], $idAttr);

        $html .= "\n\t$indent";
                
        if ($child) {
                    
            $html .= '<li '.$attrs.'>';
                
        } else {
                    
            $html .= '<li>';
                
        }
                
        $html .= $i['label'];
                
        if ($child) {
                    
            $i--;
                    
            $html .= $child;
                    
            $html .= "\n\t$indent";
                
        }
                
        $html .= '</li>';
                
    }

    $html .= "\n$indent</ul>";
            
    return $html;

} else {

    return false;
}

}

public static function removeListItem()
{
   if (is_resource(self::$item)) {

       unset(self::$item);

   } else {

        self::$item = [];

   }

}

}