<?php
// includes/partner.php

class Partner {
    
    /**
     * Get all vendors (partners with type vendor or both)
     */
    public static function getVendors() {
        try {
            $pdo = Database::getInstance();
            
            $sql = "SELECT * FROM partners 
                    WHERE (partner_type = 'vendor' OR partner_type = 'both') 
                    AND is_active = true 
                    ORDER BY partner_name";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Partner::getVendors() Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get partner by ID
     */
    public static function getById($id) {
        try {
            $pdo = Database::getInstance();
            
            $sql = "SELECT * FROM partners WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Partner::getById() Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new partner
     */
    public static function create($data) {
        try {
            $pdo = Database::getInstance();
            
            // Generate partner code if empty
            if (empty($data['partner_code'])) {
                $data['partner_code'] = self::generatePartnerCode($data['partner_type']);
            }
            
            $sql = "INSERT INTO partners (
                partner_code, partner_name, partner_type, contact_person, 
                email, phone, website, tax_id, credit_limit, payment_terms,
                currency_code, is_active, notes
            ) VALUES (
                :partner_code, :partner_name, :partner_type, :contact_person,
                :email, :phone, :website, :tax_id, :credit_limit, :payment_terms,
                :currency_code, :is_active, :notes
            )";
            
            $stmt = $pdo->prepare($sql);
            
            $params = [
                ':partner_code' => $data['partner_code'],
                ':partner_name' => $data['partner_name'],
                ':partner_type' => $data['partner_type'],
                ':contact_person' => $data['contact_person'] ?? null,
                ':email' => $data['email'] ?? null,
                ':phone' => $data['phone'] ?? null,
                ':website' => $data['website'] ?? null,
                ':tax_id' => $data['tax_id'] ?? null,
                ':credit_limit' => $data['credit_limit'] ?? 0,
                ':payment_terms' => $data['payment_terms'] ?? null,
                ':currency_code' => $data['currency_code'] ?? 'IDR',
                ':is_active' => $data['is_active'] ?? true,
                ':notes' => $data['notes'] ?? null
            ];
            
            $result = $stmt->execute($params);
            
            return $result ? $pdo->lastInsertId() : false;
            
        } catch (PDOException $e) {
            error_log("Partner::create() Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update partner
     */
    public static function update($id, $data) {
        try {
            $pdo = Database::getInstance();
            
            $sql = "UPDATE partners SET 
                partner_name = :partner_name,
                partner_type = :partner_type,
                contact_person = :contact_person,
                email = :email,
                phone = :phone,
                website = :website,
                tax_id = :tax_id,
                credit_limit = :credit_limit,
                payment_terms = :payment_terms,
                currency_code = :currency_code,
                is_active = :is_active,
                notes = :notes,
                updated_at = NOW()
            WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            
            $params = [
                ':id' => $id,
                ':partner_name' => $data['partner_name'],
                ':partner_type' => $data['partner_type'],
                ':contact_person' => $data['contact_person'] ?? null,
                ':email' => $data['email'] ?? null,
                ':phone' => $data['phone'] ?? null,
                ':website' => $data['website'] ?? null,
                ':tax_id' => $data['tax_id'] ?? null,
                ':credit_limit' => $data['credit_limit'] ?? 0,
                ':payment_terms' => $data['payment_terms'] ?? null,
                ':currency_code' => $data['currency_code'] ?? 'IDR',
                ':is_active' => $data['is_active'] ?? true,
                ':notes' => $data['notes'] ?? null
            ];
            
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log("Partner::update() Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate partner code
     */
    private static function generatePartnerCode($type) {
        try {
            $pdo = Database::getInstance();
            
            $prefix = strtoupper(substr($type, 0, 1)); // V for vendor, C for customer, B for both
            
            $sql = "SELECT partner_code FROM partners WHERE partner_code LIKE ? ORDER BY id DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$prefix . '-%']);
            $lastPartner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lastPartner && preg_match('/' . $prefix . '-(\d+)/', $lastPartner['partner_code'], $matches)) {
                $nextNumber = intval($matches[1]) + 1;
            } else {
                $nextNumber = 1;
            }
            
            return $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            return $prefix . '-0001';
        }
    }
    
    /**
     * Get partner addresses
     */
    public static function getAddresses($partnerId) {
        try {
            $pdo = Database::getInstance();
            
            $sql = "SELECT * FROM partner_addresses WHERE partner_id = ? ORDER BY is_primary DESC, address_type";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$partnerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Partner::getAddresses() Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all partners with pagination
     */
    public static function getAll($page = 1, $perPage = 20, $search = '') {
        try {
            $pdo = Database::getInstance();
            
            $offset = ($page - 1) * $perPage;
            
            $sql = "SELECT * FROM partners WHERE 1=1";
            $countSql = "SELECT COUNT(*) as total FROM partners WHERE 1=1";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (partner_name ILIKE ? OR partner_code ILIKE ? OR contact_person ILIKE ?)";
                $countSql .= " AND (partner_name ILIKE ? OR partner_code ILIKE ? OR contact_person ILIKE ?)";
                $searchTerm = "%$search%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }
            
            $sql .= " ORDER BY partner_name LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'partners' => $partners,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ];
            
        } catch (PDOException $e) {
            error_log("Partner::getAll() Error: " . $e->getMessage());
            return ['partners' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0];
        }
    }
}
?>