<?php
// includes/partner.php

class Partner
{

    /**
     * Get all vendors (partners with type vendor or both)
     */
    // Tambahkan method ini jika belum ada di class Partner
    public static function getVendors()
    {
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
    public static function getById($id)
    {
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

    public static function create($data)
    {
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
        ) RETURNING id"; // ⬅️ PASTIKAN ADA RETURNING id UNTUK PostgreSQL

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

            $stmt->execute($params);
            $partnerId = $stmt->fetchColumn(); // ⬅️ GET THE RETURNED ID

            return $partnerId ?: false;
        } catch (PDOException $e) {
            error_log("Partner::create() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update partner
     */
    public static function update($id, $data)
    {
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
    private static function generatePartnerCode($type)
    {
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
    public static function getAddresses($partnerId)
    {
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

    public static function getAll($page = 1, $perPage = 20, $search = '')
    {
        try {
            $pdo = Database::getInstance();

            error_log("=== PARTNER::GETALL CALLED ===");
            error_log("Page: $page, PerPage: $perPage, Search: '$search'");

            $offset = ($page - 1) * $perPage;

            // Simple base query - remove is_active filter untuk testing
            $sql = "SELECT * FROM partners WHERE 1=1";
            $countSql = "SELECT COUNT(*) as total FROM partners WHERE 1=1";
            $params = [];
            $countParams = [];

            // Add search condition if provided
            if (!empty($search)) {
                $sql .= " AND (partner_name LIKE ? OR partner_code LIKE ? OR contact_person LIKE ?)";
                $countSql .= " AND (partner_name LIKE ? OR partner_code LIKE ? OR contact_person LIKE ?)";
                $searchTerm = "%$search%";
                $params = array_fill(0, 3, $searchTerm);
                $countParams = array_fill(0, 3, $searchTerm);
            }

            // Add ordering and pagination
            $sql .= " ORDER BY partner_name LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;

            error_log("SQL: " . $sql);
            error_log("Params: " . print_r($params, true));

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("Partners found: " . count($partners));

            // Get total count
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            $result = [
                'partners' => $partners,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ];

            error_log("Returning: " . count($partners) . " partners, total: $total");

            return $result;
        } catch (PDOException $e) {
            error_log("❌ Partner::getAll() Error: " . $e->getMessage());
            error_log("Error Info: " . print_r($e->errorInfo, true));
            return [
                'partners' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }
    }

    /**
     * Add address to partner
     */
    public static function addAddress($partnerId, $data)
    {
        try {
            error_log("=== PARTNER::ADDADDRESS FINAL ===");

            $pdo = Database::getInstance();

            // Convert boolean properly
            $isPrimary = filter_var($data['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // If set as primary, update others
            if ($isPrimary) {
                $updateSql = "UPDATE partner_addresses SET is_primary = false WHERE partner_id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$partnerId]);
            }

            // FINAL VERSION - tanpa address_name
            $sql = "INSERT INTO partner_addresses (
            partner_id, address_type, address_line1, address_line2,
            city, state, postal_code, country, contact_person, phone, notes, is_primary
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);

            $params = [
                (int)$partnerId,
                $data['address_type'] ?? 'billing',
                $data['address_line1'] ?? '',
                $data['address_line2'] ?? null,
                $data['city'] ?? '',
                $data['state'] ?? null,
                $data['postal_code'] ?? null,
                $data['country'] ?? 'Indonesia',
                $data['contact_person'] ?? null,
                $data['phone'] ?? null,
                $data['notes'] ?? null,
                $isPrimary
            ];

            error_log("Final Params: " . print_r($params, true));

            $result = $stmt->execute($params);
            error_log("Execute result: " . ($result ? 'SUCCESS' : 'FAILED'));

            return $result;
        } catch (PDOException $e) {
            error_log("❌ PDO Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update address
     */
    public static function updateAddress($addressId, $data)
    {
        try {
            $pdo = Database::getInstance();

            // Get partner_id from address
            $getSql = "SELECT partner_id FROM partner_addresses WHERE id = ?";
            $getStmt = $pdo->prepare($getSql);
            $getStmt->execute([$addressId]);
            $address = $getStmt->fetch(PDO::FETCH_ASSOC);

            if (!$address) {
                throw new Exception("Address not found");
            }

            // Convert boolean
            $isPrimary = filter_var($data['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // If set as primary, update others
            if ($isPrimary) {
                $updateSql = "UPDATE partner_addresses SET is_primary = false WHERE partner_id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$address['partner_id']]);
            }

            // **PERBAIKAN: Sesuaikan SQL dengan struktur tabel**
            $sql = "UPDATE partner_addresses SET 
            address_type = ?, address_line1 = ?, address_line2 = ?,
            city = ?, state = ?, postal_code = ?, country = ?, 
            contact_person = ?, phone = ?, notes = ?, is_primary = ?,
            updated_at = NOW()
            WHERE id = ?";

            $stmt = $pdo->prepare($sql);

            return $stmt->execute([
                $data['address_type'],
                $data['address_line1'],
                $data['address_line2'],
                $data['city'],
                $data['state'],
                $data['postal_code'],
                $data['country'],
                $data['contact_person'],
                $data['phone'],
                $data['notes'],
                $isPrimary,
                $addressId
            ]);
        } catch (PDOException $e) {
            error_log("Partner::updateAddress() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete address
     */
    public static function deleteAddress($addressId)
    {
        try {
            $pdo = Database::getInstance();

            $sql = "DELETE FROM partner_addresses WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$addressId]);
        } catch (PDOException $e) {
            error_log("Partner::deleteAddress() Error: " . $e->getMessage());
            return false;
        }
    }
}
