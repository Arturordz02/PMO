<?php
namespace App\Core;

use PDO;

/**
 * PMO SOLUTIONS - Modelo Base (Base Model)
 * 
 * Clase abstracta que provee acceso centralizado a la instancia PDO de la Base de Datos.
 */
abstract class Model {

    protected ?PDO $db = null;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Verifica si la conexión a base de datos está activa y disponible
     */
    public function isDbConnected(): bool {
        return $this->db !== null;
    }
}

