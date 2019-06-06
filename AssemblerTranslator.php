<?php

include 'topDownAnalyzer.php';

/**
 * 
     R1 accumulator
     W1 register
     MOVE R1, abc // abc -- IDENTIFIER
     MOVE R1, 1.0 // FLOAT
     MOVE R1, 1   // INTEGER

     PUSH, POP
     ADD R1, W1
     SUB R1, W1
     MUL R1, W1
     ADD R1, W1
 */
class AssemblerTranslator
{
    /**
     *
     * @var topDownAnalyzer | null
     */
    public $analyzer = null;
    
    public static $GRAMMAR = [
        'P' => [
            ['\INTEGER'],
            ['\IDENTIFIER'],
            ['\FLOAT'],
            ['(', '\S', ')'],
        ],
        'T' => [
            ['\P'],
            ['\P', '*', '\T'],
        ],
        'S' => [
            ['\T', '+', '\S'],
            ['\T'],
        ],
    ];

    public static $regExpClasses = [
        'IDENTIFIER' => '/^\w[\w\d_]*$/',
    ];

    
    function __construct()
    {
        $this->analyzer = new topDownAnalyzer(self::$GRAMMAR, self::$regExpClasses);
    }
    
    public function translate($inputString)
    {
        $res = $this->analyzer->parse(explode(' ', $inputString), 'S');
        
        $PROGRAMS = [];

        foreach ($res as $variety) {
            $PROGRAM = [];
            $tree = $variety['line'];
            $this->S_handler($PROGRAM, $tree);
            $PROGRAMS[] = $PROGRAM;
        }
        
        return $PROGRAMS;
    }
    
    function S_handler(array &$program, $list) 
    {
        switch (count($list)) {
            case 2: // S --> T
                $this->T_handler($program, $list[1]);
                break;
            case 4: // S --> T + S
                $this->S_handler($program, $list[3]);
                $program[] = 'PUSH R1';
                $this->T_handler($program, $list[1]);
                $program[] = 'POP  W1';
                $program[] = 'ADD  R1, W1';
        }
    }

    function T_handler(array &$program, $list) 
    {
        switch (count($list)) {
            case 2: // T --> P
                $this->P_handler($program, $list[1]);
                // Add to the program nothing -- Operand in R1;
                break; 
            case 4: // T --> P * T
                $this->T_handler($program, $list[3]);
                $program[] = 'PUSH R1';
                $this->P_handler($program, $list[1]);
                $program[] = 'POP  W1';
                $program[] = 'MUL  R1, W1';
        }
    }

    function P_handler(array &$program, $list)
    {
        switch (count($list)) {
            case 2: //   P -->  IDENTIFIER | FLOAT | INTEGER 
                $program[] = 'MOVE R1, '. $list[1][1];
                break; 
            case 4: // P --> ( S )
                $this->S_handler($program, $list[2]);
        }
    }
}