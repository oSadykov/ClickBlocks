<?php
/**
 * ClickBlocks.PHP v. 1.0
 * 
 * Copyright (C) 2014  SARITASA LLC
 * http://www.saritasa.com   
 * 
 * This framework is free software. You can redistribute it and/or modify 
 * it under the terms of either the current ClickBlocks.PHP License
 * (viewable at theclickblocks.com) or the License that was distributed with
 * this file.   
 *  
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * 
 * You should have received a copy of the ClickBlocks.PHP License
 * along with this program.    
 * 
 * Responsibility of this file: phpparser.php 
 * 
 * @category   Helper
 * @package    Core
 * @copyright  2007-2014 SARITASA LLC <info@saritasa.com>
 * @link       http://www.saritasa.com
 * @since      File available since Release 1.0.0        
 */ 

namespace ClickBlocks\Utils\PHP;

/**
 * Contains the set of static methods for manipulations with php code.
 *                                                                    
 * @version 1.0.0
 * @package cb.utils.php
 */
class Tools
{
  /**
   * Splits full class name into array containing two elements of the following structure: [class namespace, own class name].
   * 
   * @param string|object $class
   * @return array
   * @access static
   */
  public static function splitClassName($class)
  {
    if (is_object($class)) $class = get_class($class);
    $k = strrpos($class, '\\');
    if ($k === false) return ['\\', $class];
    return [substr($class, 0, $k), substr($class, $k + 1)];
  }
  
  /**
   * Returns the namespace of the full class name.
   * 
   * @param string|object $class   
   * @return string      
   * @access static   
   */       
  public static function getNamespace($class)
  {
    return static::splitClassName($class)[0];
  }
  
  /**
   * Returns the class name of the full class name (class name with namespace).
   * 
   * @param string|object $class   
   * @return string      
   * @access static   
   */       
  public static function getClassName($class)
  {
    return static::splitClassName($class)[1];
  }
          
  /**
   * Searches the first occurrence or all occurrences of the given PHP code in another PHP code.
   * Returns a numeric array of two elements or FALSE if the PHP fragment is not found.
   * The first element is the index of the first token in the haystack. 
   * The second element is the index of the last token in the haystack.
   * 
   * @param string $needle - the PHP code which we want to find.
   * @param string $haystack  - the PHP code in which we want to find our PHP fragment.
   * @param boolean $all - determines whether all occurrences of the PHP fragment will be found.
   * @return array|boolean
   * @access public
   * @static                          
   */                    
  public static function search($needle, $haystack, $all = false)
  {
    $x = [];
    foreach (Tokenizer::parse($needle) as $token) if (Tokenizer::isSemanticToken($token)) $x[] = $token;
    $m = count($x);
    if ($m == 0) return false;
    $y = Tokenizer::parse($haystack);
    $n = count($y) - $m;
    if ($n < 0) return false;
    $res = [];
    for ($i = 0, $k = 0; $i < $n; $i++)
    {
      $token = $y[$i];
      if (!Tokenizer::isSemanticToken($token)) continue;
      if (Tokenizer::isEqual($x[$k], $token)) 
      {
        $k++;
        if ($k == 1) $start = $i;
        if ($k == $m) 
        {
          if (!$all) return [$start, $i];
          else 
          {
            $res[] = [$start, $i];
            $k = 0;
          }
        }
      }
      else $k = 0;
    }
    return $res ?: false;
  }
   
  /**
   * Checks whether the given PHP code is contained in other one.
   * 
   * @param string $needle - the PHP code which we want to find.
   * @param string $haystack  - the PHP code in which we want to find our PHP fragment.
   * @return boolean
   * @static                    
   */       
  public static function in($needle, $haystack)
  {
    return static::search($needle, $haystack) !== false;
  }
   
  /**
   * Replaces all occurrences of the PHP code fragment in the given PHP code string with another PHP fragment.
   * 
   * @param string $search - the PHP fragment being searched for.
   * @param string $replace - the replacement PHP code that replaces found $search values.
   * @param string $subject - the PHP code string being searched and replaced on.
   * @return string
   * @access public
   * @static                           
   */       
  public static function replace($search, $replace, $subject)
  {
    $x = [];
    foreach (Tokenizer::parse($search) as $token) if (Tokenizer::isSemanticToken($token)) $x[] = $token;
    $m = count($x);
    if ($m == 0) return $subject;
    $y = Tokenizer::parse($subject);
    $n = count($y);
    if ($n < 0) return $subject;
    $res = '';
    for ($i = 0; $i < $n; $i++)
    {
      $token = $y[$i];
      if (Tokenizer::isEqual($x[0], $token))
      {
        $fragment = ''; $k = 1;
        do
        {
          $fragment .= is_array($token) ? $token[1] : $token;
          if ($k == $m)
          {
            $res .= $replace;
            $token = $fragment = '';
            break;
          }
          $i++;
          if ($i >= $n) break;
          $token = $y[$i];
        }
        while (!Tokenizer::isSemanticToken($token) || Tokenizer::isEqual($x[$k++], $token));
        $res .= $fragment;
      }
      $res .= is_array($token) ? $token[1] : $token;
    }
    return $res;
  }
   
  /**
   * Removes the given PHP code fragment from the PHP code string.
   * 
   * @param string $search - the PHP fragment being searched for.
   * @param string $subject - the PHP code string being searched and removed from.
   * @return string
   * @access public
   * @static                       
   */       
  public static function remove($search, $subject)
  {
    return static::replace($search, '', $subject);
  }
  
  /**
   * Converts mixed PHP value to JS value.
   * If the given value is an array and the second argument is FALSE the array will be treated as an array of values.
   *
   * @param mixed $value - a PHP value to be converted.
   * @param boolean $isArray - determines whether the given value is an array of values or not.
   * @param string $jsMark - determines a prefix mark of the JavaScript code.
   * @return string|array - returns an array of converted values or a string.
   */
  public static function php2js($value, $isArray = true, $jsMark = null)
  {
    $rep = ["\r" => '\\r', "\n" => '\\n', "'" => "\'", '\\' => '\\\\'];
    if (is_object($value)) $value = get_object_vars($value);
    if (is_array($value)) 
    {
      if ($isArray)
      {
        $tmp = []; $isNumeric = true;
        foreach ($value as $k => $v) 
        {
          if (!is_numeric($k))
          {
            $isNumeric = false;
            break;
          }
        }
        if ($isNumeric)
        {
          foreach ($value as $k => $v) $tmp[] = static::php2js($v, true, $jsMark);
          return '[' . implode(', ', $tmp) . ']';
        }
        foreach ($value as $k => $v) $tmp[] = "'" . strtr($k, $rep) . "': " . static::php2js($v, true, $jsMark);
        return '{' . implode(', ', $tmp) . '}';
      }
      else
      {
        foreach ($value as &$v) $v = static::php2js($v, true, $jsMark);
        return $value;
      }
    }
    if (is_null($value)) return 'undefined';
    if (is_bool($value)) return $value ? 'true' : 'false';
    if (is_numeric($value)) return $value;
    if (strlen($jsMark) && substr($value, 0, strlen($jsMark)) == $jsMark) return substr($value, strlen($jsMark));
    return "'" . strtr($value, $rep) . "'";
  }
}