<?php

// Interface para el patrón Strategy
// PRINCIPIO DE ABSTRACCIÓN: Define un contrato común para todas las estrategias de sesión
interface SessionStrategy {
    public function getDuration(): string;
    public function getPrice(): string;
    public function getDescription(): string;
    public function getIcon(): string;
}

// Clase para manejar todas las estrategias de sesión
class SessionManager {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getAllStrategies(): array {
        return [
            'cobertura de evento' => new EventCoverageStrategy(),
            'temática' => new ThematicSessionStrategy(),
            'estudio' => new StudioSessionStrategy(),
            'exterior' => new OutdoorSessionStrategy(),
            'corporativo' => new CorporateSessionStrategy(),
            'familiar' => new FamilySessionStrategy()
        ];
    }
    
    public function getStrategy(string $type): SessionStrategy {
        $strategies = $this->getAllStrategies();
        if (!isset($strategies[$type])) {
            throw new Exception("Tipo de sesión no soportado: " . $type);
        }
        return $strategies[$type];
    }
}

// Estrategia para Cobertura de Evento
class EventCoverageStrategy implements SessionStrategy {
    public function getDuration(): string {
        return '4h 0min';
    }
    
    public function getPrice(): string {
        return '$500';
    }
    
    public function getDescription(): string {
        return 'Fotografía profesional para eventos especiales';
    }
    
    public function getIcon(): string {
        return 'bi bi-camera2';
    }
}

// Estrategia para Sesión Temática
class ThematicSessionStrategy implements SessionStrategy {
    public function getDuration(): string {
        return '2h 0min';
    }
    
    public function getPrice(): string {
        return '$300';
    }
    
    public function getDescription(): string {
        return 'Sesión fotográfica con temática personalizada';
    }
    
    public function getIcon(): string {
        return 'bi bi-stars';
    }
}

// Estrategia para Sesión en Estudio
class StudioSessionStrategy implements SessionStrategy {
    public function getDuration(): string {
        return '1h 30min';
    }
    
    public function getPrice(): string {
        return '$250';
    }
    
    public function getDescription(): string {
        return 'Sesión en estudio profesional con iluminación controlada';
    }
    
    public function getIcon(): string {
        return 'bi bi-lightbulb';
    }
}

// Estrategia para Sesión Exterior
class OutdoorSessionStrategy implements SessionStrategy {
    public function getDuration(): string {
        return '2h 30min';
    }
    
    public function getPrice(): string {
        return '$350';
    }
    
    public function getDescription(): string {
        return 'Sesión fotográfica en locaciones exteriores';
    }
    
    public function getIcon(): string {
        return 'bi bi-tree';
    }
}

// Estrategia para Sesión Corporativa
class CorporateSessionStrategy implements SessionStrategy {
    public function getDuration(): string {
        return '1h 0min';
    }
    
    public function getPrice(): string {
        return '$200';
    }
    
    public function getDescription(): string {
        return 'Fotografías profesionales para uso empresarial';
    }
    
    public function getIcon(): string {
        return 'bi bi-person-badge';
    }
}

// Estrategia para Sesión Familiar
class FamilySessionStrategy implements SessionStrategy {
    public function getDuration(): string {
        return '2h 0min';
    }
    
    public function getPrice(): string {
        return '$280';
    }
    
    public function getDescription(): string {
        return 'Sesión fotográfica familiar en estudio o exterior';
    }
    
    public function getIcon(): string {
        return 'bi bi-people';
    }
}

// Contexto que usa la estrategia
// PRINCIPIO DE ENCAPSULAMIENTO: La propiedad $strategy es privada, protegiendo su acceso directo
class SessionContext {
    private $strategy;
    
    // PRINCIPIO DE POLIMORFISMO: Acepta cualquier implementación de SessionStrategy
    public function setStrategy(SessionStrategy $strategy): void {
        $this->strategy = $strategy;
    }
    
    public function getDuration(): string {
        return $this->strategy->getDuration();
    }
    
    public function getPrice(): string {
        return $this->strategy->getPrice();
    }
    
    public function getDescription(): string {
        return $this->strategy->getDescription();
    }
    
    public function getIcon(): string {
        return $this->strategy->getIcon();
    }
}

// Función para obtener todas las estrategias disponibles
function getAllSessionStrategies(): array {
    return [
        'cobertura de evento' => new EventCoverageStrategy(),
        'temática' => new ThematicSessionStrategy(),
        'estudio' => new StudioSessionStrategy(),
        'exterior' => new OutdoorSessionStrategy(),
        'corporativo' => new CorporateSessionStrategy(),
        'familiar' => new FamilySessionStrategy()
    ];
}

// Función para obtener la estrategia correcta basada en el tipo de sesión
function getSessionStrategy(string $type): SessionStrategy {
    $strategies = getAllSessionStrategies();
    if (!isset($strategies[$type])) {
        throw new Exception("Tipo de sesión no soportado: " . $type);
    }
    return $strategies[$type];
}