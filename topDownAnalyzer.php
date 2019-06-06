<?php

class topDownAnalyzer
{
    const INTEGER = '/^-?(0|[1-9]\d*)$/';
    const FLOAT   = '/^-?(0|[1-9]\d*)?\.\d+$/';
    
    /**
     * @var array
     */
    public $GRAMMAR = [];

    public $regExpClasses = [
        'INTEGER' => self::INTEGER,
        'FLOAT'   => self::FLOAT,
    ];
    
    /**
     *
     * @var array
     */
    public $inputWords = [];

    /**
     * @var integer
     */
    public $inputPhraseLength  = 0;
    
    public function __construct($GRAMMAR, $regExpClasses = [])
    {
        $this->GRAMMAR = $GRAMMAR;
        $this->regExpClasses = array_merge($this->regExpClasses, $regExpClasses);
    }
    
    function is_terminal($elem)
    {
        return $elem[0] !== '\\';
    }

    function is_reg_exp_class($line_word)
    {
        return array_key_exists(mb_substr($line_word, 1), $this->regExpClasses);
    }

    function handle_class($class_name, $offset = 0, $outer = false)
    {
        $results = [];
        $class   = $this->GRAMMAR[$class_name];
        $line    = [$class_name];
       
        foreach ($class as $class_line) {
            $result = $this->handle_class_line($line, $class_line, $offset);

            if ($result !== false) {
                foreach ($result as $result_item) {
                    if ($result_item['offset'] === $this->inputPhraseLength || !$outer)       // Мы дошли до конца строки.
                        $results[] = $result_item;
                    }
            }
        }

        return $results;
    }

    function handle_class_line($line, $class_line, $offset = 0, $class_line_offset = 0)
    {
        // строка класса пройдена до конца
        if ($class_line_offset >= count($class_line) ) {
           return false;
        }

        $classLineLength = count($class_line);
        
        for ($i = $class_line_offset; $i < $classLineLength; $i++) {

            if ($offset >= $this->inputPhraseLength) {  // Входное выражение уже закончилось, а строка класса --- нет.
                return false;
            }

            $line_word = $class_line[$i];

            $symbol = $this->inputWords[$offset];

            if ($this->is_terminal($line_word)) {
                if ($line_word === $symbol) {
                    $line[] = $line_word;
                    $offset++; // Указатель на следующее слово.
                } else {
                    return false; // Значит, и вся строка не годится.
                }
            } elseif ($this->is_reg_exp_class($line_word)) {
                $subst_class_name = mb_substr($line_word, 1);
                if (!empty(preg_match($this->regExpClasses[$subst_class_name], $symbol))) {
                    $line[] = [$subst_class_name, $symbol];
                    $offset++; // Указатель на следующее слово.
                } else {
                    return false; // Значит, и вся строка не годится.
                }
            } else {   // $line_word is a class
                
                $subst_class_name = mb_substr($line_word, 1);
                $lines_to_embed = $this->handle_class($subst_class_name, $offset);
                
                if (empty($lines_to_embed)) {
                    return false; // Значит, и вся строка не годится.
                } else {
                    if (count($lines_to_embed) > 1) {
                        $res = [];
                        foreach($lines_to_embed as $line_to_embed) {
                            $new_line = $line;
                            $new_line[] = $line_to_embed['line'];
                            $class_line_to_merge = 
                                $i === $classLineLength - 1 ?
                                [[
                                    'line' => $new_line,
                                    'offset' => $line_to_embed['offset'],
                                ]] :
                                $this->handle_class_line($new_line, $class_line, $line_to_embed['offset'], $i+1);
                            
                            if ($class_line_to_merge !== false) {
                                $res = array_merge($res, $class_line_to_merge);
                            }
                        }

                        return empty($res) ? false : $res;

                    } else {
                        $line_to_embed = $lines_to_embed[0];
                        $line[] = $line_to_embed['line'];
                        $offset = $line_to_embed['offset'];
                    }
                }
            }
        }

        return [[  // Разбор строки класса выполнен полностью
           'offset'  => $offset,
           'line'    => $line,
        ]];
    }
    
    public function parse($inputWords, $startClassName)
    {
        $this->inputWords = $inputWords;
        $this->inputPhraseLength  = count($this->inputWords);
        
        return $this->handle_class($startClassName, 0, true);
    }
}
