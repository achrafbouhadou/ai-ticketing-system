<?php

namespace App\DTOs;

class ClassificationDTO
{
    public function __construct(
        public readonly ?string $category,     
        public readonly ?string $explanation,  
        public readonly ?float $confidence    
    ) {}
}
