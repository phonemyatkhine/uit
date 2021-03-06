<?php


namespace heinthanth\Uit\Parser;


use heinthanth\Uit\Lexer\Token;
use heinthanth\Uit\Parser\OperationNode\BinOperationNode;
use heinthanth\Uit\Parser\OperationNode\MonoOperationNode;
use heinthanth\Uit\Parser\OperationNode\NumberNode;
use heinthanth\Uit\Parser\OperationNode\OperationNodeInterface;

class Parser
{
    /**
     * Current index of token array
     * @var int
     */
    private int $index = -1;

    /**
     * Current token
     * @var Token
     */
    private Token $currentToken;

    /**
     * Parser constructor.
     * @param array $tokens list of tokens from Lexer
     */
    public function __construct(private array $tokens)
    {
        $this->goNext();
    }

    /**
     * Parse Token to OperationNodes
     * @return OperationNodeInterface
     */
    public function parse(): OperationNodeInterface
    {
        $node = $this->expression();
        if ($this->currentToken->type !== T_EOF) {
            // not reached end of token. Must be error exist.
            die("Error: Invalid Syntax" . PHP_EOL);
        }
        return $node;
    }

    /**
     * Move to next token by incrementing index
     * and assign current Token
     */
    private function goNext(): void
    {
        $this->index++;
        if ($this->index < count($this->tokens)) {
            $this->currentToken = $this->tokens[$this->index];
        }
    }

    /**
     * Parse Expression
     * eg. ( term (+|-) term ), (let a = 5)
     *
     * @return OperationNodeInterface
     */
    private function expression(): OperationNodeInterface
    {
        $leftNode = $this->term();
        while ($this->currentToken->type === T_PLUS || $this->currentToken->type === T_MINUS) {
            $operator = $this->currentToken;
            $this->goNext();
            $rightNode = $this->term();
            $leftNode = new BinOperationNode($leftNode, $operator, $rightNode);
        }
        return $leftNode;
    }

    /**
     * Parse Term
     * eg. ( factor (*|/) factor )
     * @return OperationNodeInterface
     */
    private function term(): OperationNodeInterface
    {
        $leftNode = $this->factor();
        while ($this->currentToken->type === T_STAR || $this->currentToken->type === T_SLASH || $this->currentToken->type === T_PERCENT) {
            $operator = $this->currentToken;
            $this->goNext();
            $rightNode = $this->factor();
            $leftNode = new BinOperationNode($leftNode, $operator, $rightNode);
        }
        return $leftNode;
    }

    /**
     * Parser Factor
     * eg. (1,2) or -1
     *
     * @return OperationNodeInterface
     */
    private function factor(): OperationNodeInterface
    {
        $token = $this->currentToken;
        // if mono operation like -1, -(-2)
        if ($token->type === T_PLUS || $token->type === T_MINUS) {
            $this->goNext();
            $factor = $this->factor();
            return new MonoOperationNode($token, $factor);
        }
        // else just solve number or other functions
        return $this->atom();
    }

    /**
     * Solve number node, power node,etc
     */
    private function atom(): OperationNodeInterface
    {
        $token = $this->currentToken;
        if ($token->type === T_NUMBER) {
            $this->goNext();
            return new NumberNode($token);
        } elseif ($token->type === T_LPARAN) {
            $this->goNext();
            $expr = $this->expression();
            if ($this->currentToken->type === T_RPARAN) {
                $this->goNext();
                return $expr;
            }
            // something went wrong here. invalid syntax missing ')'
            die("Error: Invalid Syntax. Expecting ')'" . PHP_EOL);
        }
        // something went wrong here. invalid syntax
        die("Error: Invalid Syntax. Expecting Number" . PHP_EOL);
    }
}