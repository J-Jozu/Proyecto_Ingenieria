<?php

// Interface para el patrón Strategy de impresiones
// PRINCIPIO DE ABSTRACCIÓN: Define un contrato común para todas las estrategias de impresión
interface PrintStrategy {
    public function getName(): string;
    public function getDescription(): string;
    public function getIcon(): string;
    public function getColor(): string;
    public function getMultiplier(): float;
}

// Estrategia para Impresión Estándar
class StandardPrintStrategy implements PrintStrategy {
    public function getName(): string { return 'Impresión Estándar'; }
    public function getDescription(): string { return 'Papel fotográfico mate de alta calidad'; }
    public function getIcon(): string { return 'bi-camera'; }
    public function getColor(): string { return 'bg-primary'; }
    public function getMultiplier(): float { return 1.0; }
}

// Estrategia para Impresión Premium
class PremiumPrintStrategy implements PrintStrategy {
    public function getName(): string { return 'Impresión Premium'; }
    public function getDescription(): string { return 'Papel fotográfico brillante profesional'; }
    public function getIcon(): string { return 'bi-star'; }
    public function getColor(): string { return 'bg-purple'; }
    public function getMultiplier(): float { return 1.5; }
}

// Estrategia para Impresión en Canvas
class CanvasPrintStrategy implements PrintStrategy {
    public function getName(): string { return 'Impresión en Canvas'; }
    public function getDescription(): string { return 'Lienzo de algodón premium estirado'; }
    public function getIcon(): string { return 'bi-easel'; }
    public function getColor(): string { return 'bg-success'; }
    public function getMultiplier(): float { return 2.0; }
}

// Estrategia para Impresión en Metal
class MetalPrintStrategy implements PrintStrategy {
    public function getName(): string { return 'Impresión en Metal'; }
    public function getDescription(): string { return 'Aluminio con acabado brillante duradero'; }
    public function getIcon(): string { return 'bi-award'; }
    public function getColor(): string { return 'bg-warning'; }
    public function getMultiplier(): float { return 3.0; }
}

// Contexto que usa la estrategia
// PRINCIPIO DE ENCAPSULAMIENTO: La propiedad $strategy es privada, protegiendo su acceso directo
class Impresiones {
    private $strategy;

    public function __construct($tipo = 'standard') {
        $this->setStrategy(getPrintStrategy($tipo));
    }

    // PRINCIPIO DE POLIMORFISMO: Acepta cualquier implementación de PrintStrategy
    public function setStrategy(PrintStrategy $strategy): void {
        $this->strategy = $strategy;
    }

    public function getName(): string {
        return $this->strategy->getName();
    }
    public function getDescription(): string {
        return $this->strategy->getDescription();
    }
    public function getIcon(): string {
        return $this->strategy->getIcon();
    }
    public function getColor(): string {
        return $this->strategy->getColor();
    }
    public function getMultiplier(): float {
        return $this->strategy->getMultiplier();
    }

    // Método para obtener todos los datos como array (similar a obtenerTipoImpresion)
    public function obtenerTipoImpresion(): array {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'multiplier' => $this->getMultiplier()
        ];
    }
}

// Función para obtener la estrategia correcta basada en el tipo de impresión
function getPrintStrategy(string $type): PrintStrategy {
    switch($type) {
        case 'premium':
            return new PremiumPrintStrategy();
        case 'canvas':
            return new CanvasPrintStrategy();
        case 'metal':
            return new MetalPrintStrategy();
        case 'standard':
        default:
            return new StandardPrintStrategy();
    }
}
