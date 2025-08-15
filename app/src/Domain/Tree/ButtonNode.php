<?php

declare(strict_types=1);

namespace App\Domain\Tree;

class ButtonNode extends AbstractTreeNode
{
    private string $buttonText;
    private string $buttonAction;

    public function __construct(
        ?int $id,
        string $name,
        int $treeId,
        ?int $parentId = null,
        int $sortOrder = 0,
        array $typeData = []
    ) {
        parent::__construct($id, $name, $treeId, $parentId, $sortOrder);
        $this->buttonText = $typeData['button_text'] ?? 'Test Btn';
        $this->buttonAction = $typeData['button_action'] ?? '';
    }

    #[\Override]
    public function getType(): string
    {
        return 'ButtonNode';
    }

    public function getButtonText(): string
    {
        return $this->buttonText;
    }

    public function getButtonAction(): string
    {
        return $this->buttonAction;
    }

    #[\Override]
    public function accept(TreeNodeVisitor $visitor): string
    {
        return $visitor->visitButtonNode($this);
    }

    #[\Override]
    public function getTypeData(): array
    {
        return [
            'button_text' => $this->buttonText,
            'button_action' => $this->buttonAction,
        ];
    }

    public function setButtonText(string $buttonText): void
    {
        $this->buttonText = $buttonText;
    }

    public function setButtonAction(string $buttonAction): void
    {
        $this->buttonAction = $buttonAction;
    }
}
