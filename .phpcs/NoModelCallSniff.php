<?php

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class NoModelCallSniff implements Sniff
{
    public function register()
    {
        // Registering to listen for T_VARIABLE token
        return [T_VARIABLE];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check if the variable is '$this'
        if ($tokens[$stackPtr]['content'] === '$this') {
            // Look ahead to see if '->models' follows
            $nextPtr = $phpcsFile->findNext(T_OBJECT_OPERATOR, $stackPtr + 1);
            if ($nextPtr !== false && $tokens[$nextPtr + 1]['content'] === 'models') {
                $error = 'Model calls ($this->models) are not allowed in update files.';
                $phpcsFile->addError($error, $stackPtr, 'NoModelCall');
            }
        }
    }
}
