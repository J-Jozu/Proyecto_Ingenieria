<?php
// PRINCIPIO DE ENCAPSULAMIENTO: Clase que agrupa datos y comportamientos relacionados con una galería de fotos
class Galeria {
    public $id;
    public $token;
    public $estado;
    public $fotos = [];
    public $seleccionadas = [];

    public function __construct($galeriaData, $pdo) {
        $this->id = $galeriaData['id'];
        $this->token = $galeriaData['token'];
        $this->estado = $galeriaData['estado'];
        // Cargar fotos (evitar duplicados)
        $this->fotos = [];
        $this->seleccionadas = [];
        $stmt = $pdo->prepare("SELECT * FROM fotos WHERE galeria_id = :galeria_id");
        $stmt->execute([':galeria_id' => $this->id]);
        while ($foto = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->fotos[] = $foto;
            if ($foto['is_selected']) {
                $this->seleccionadas[] = $foto['id'];
            }
        }
    }

    // Permite seleccionar fotos y actualiza la base de datos
    public function seleccionarFotos($seleccionadas_post, $pdo) {
        foreach ($this->fotos as $foto) {
            $is_selected = in_array($foto['id'], $seleccionadas_post) ? 1 : 0;
            $foto_id = $foto['id'];
            $sql_update = "UPDATE fotos SET is_selected = :is_selected WHERE id = :foto_id";
            $pdo->prepare($sql_update)->execute([':is_selected' => $is_selected, ':foto_id' => $foto_id]);
        }
        $pdo->prepare("UPDATE galerias SET estado = 'selected' WHERE id = :galeria_id")->execute([':galeria_id' => $this->id]);
        $this->seleccionadas = $seleccionadas_post;
        $this->estado = 'selected';
        return 'Selección enviada exitosamente. ' . count($seleccionadas_post) . ' fotos seleccionadas.';
    }
      // Devuelve las fotos seleccionadas para edición
    public function entregarFotosEditables() {
        $editables = [];
        foreach ($this->fotos as $foto) {
            if (in_array($foto['id'], $this->seleccionadas)) {
                $editables[] = $foto;
            }
        }
        return $editables;
    }
}
