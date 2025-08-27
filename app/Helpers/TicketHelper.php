<?php
namespace App\Helpers;

/**
 * Ticket access control based on session allow-list
 */
class TicketHelper {
    public static function allowAccess(string $ref): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['rpai_ticket_refs'])) {
            $_SESSION['rpai_ticket_refs'] = [];
        }
        $_SESSION['rpai_ticket_refs'][] = $ref;
    }
    
    public static function canAccess(string $ref): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Admin always allowed
        if (isset($_SESSION['rpai_admin_unlocked']) && $_SESSION['rpai_admin_unlocked'] === true) {
            return true;
        }
        
        // Check session allow-list
        if (!isset($_SESSION['rpai_ticket_refs'])) {
            return false;
        }
        
        return in_array($ref, $_SESSION['rpai_ticket_refs'], true);
    }
}
