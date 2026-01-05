<?php
// app/repo.php
declare(strict_types=1);

function list_items(PDO $pdo, array $q): array {
  $where = [];
  $params = [];

  if (!empty($q['type']) && in_array($q['type'], ['lost','found'], true)) {
    $where[] = "type = :type";
    $params[':type'] = $q['type'];
  }

  if (!empty($q['status']) && in_array($q['status'], ['open','matched','returned'], true)) {
    $where[] = "status = :status";
    $params[':status'] = $q['status'];
  }

  if (!empty($q['category'])) {
    $where[] = "category LIKE :category";
    $params[':category'] = "%" . $q['category'] . "%";
  }

  if (!empty($q['location'])) {
    $where[] = "location LIKE :location";
    $params[':location'] = "%" . $q['location'] . "%";
  }

  if (!empty($q['keyword'])) {
    $where[] = "(title LIKE :kw OR description LIKE :kw)";
    $params[':kw'] = "%" . $q['keyword'] . "%";
  }

  $sql = "SELECT * FROM items";
  if ($where) $sql .= " WHERE " . implode(" AND ", $where);
  $sql .= " ORDER BY created_at DESC LIMIT 200";

  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->fetchAll();
}

function get_item(PDO $pdo, int $id): ?array {
  $st = $pdo->prepare("SELECT * FROM items WHERE id = :id");
  $st->execute([':id' => $id]);
  $row = $st->fetch();
  return $row ?: null;
}

function create_item(PDO $pdo, array $data): int {
  $sql = "INSERT INTO items
  (type,title,category,location,event_date,description,photo_path,status,contact_name,contact_channel,contact_value)
  VALUES
  (:type,:title,:category,:location,:event_date,:description,:photo_path,'open',:contact_name,:contact_channel,:contact_value)";
  $st = $pdo->prepare($sql);
  $st->execute([
    ':type' => $data['type'],
    ':title' => $data['title'],
    ':category' => $data['category'],
    ':location' => $data['location'],
    ':event_date' => $data['event_date'],
    ':description' => $data['description'] ?? null,
    ':photo_path' => $data['photo_path'] ?? null,
    ':contact_name' => $data['contact_name'] ?? null,
    ':contact_channel' => $data['contact_channel'] ?? 'none',
    ':contact_value' => $data['contact_value'] ?? null,
  ]);
  return (int)$pdo->lastInsertId();
}

function create_claim(PDO $pdo, array $data): int {
  $sql = "INSERT INTO claims (item_id, claimer_name, claimer_contact, proof_text)
          VALUES (:item_id,:name,:contact,:proof)";
  $st = $pdo->prepare($sql);
  $st->execute([
    ':item_id' => $data['item_id'],
    ':name' => $data['claimer_name'],
    ':contact' => $data['claimer_contact'],
    ':proof' => $data['proof_text'],
  ]);
  return (int)$pdo->lastInsertId();
}

function list_claims(PDO $pdo, string $status = 'pending'): array {
  $st = $pdo->prepare("
    SELECT c.*, i.title, i.type, i.status AS item_status
    FROM claims c
    JOIN items i ON i.id = c.item_id
    WHERE c.status = :st
    ORDER BY c.created_at DESC
    LIMIT 200
  ");
  $st->execute([':st' => $status]);
  return $st->fetchAll();
}

function review_claim(PDO $pdo, int $claimId, string $decision, ?string $note): void {
  if (!in_array($decision, ['approved','rejected'], true)) return;

  // ambil claim + item_id
  $st = $pdo->prepare("SELECT * FROM claims WHERE id = :id");
  $st->execute([':id' => $claimId]);
  $claim = $st->fetch();
  if (!$claim) return;

  $pdo->beginTransaction();

  // update claim
  $st1 = $pdo->prepare("
    UPDATE claims SET status = :s, admin_note = :n, reviewed_at = NOW()
    WHERE id = :id
  ");
  $st1->execute([':s' => $decision, ':n' => $note, ':id' => $claimId]);

  // jika approved -> item jadi returned
  if ($decision === 'approved') {
    $st2 = $pdo->prepare("UPDATE items SET status = 'returned' WHERE id = :item_id");
    $st2->execute([':item_id' => (int)$claim['item_id']]);

    // opsional: reject klaim pending lain untuk item ini
    $st3 = $pdo->prepare("
      UPDATE claims SET status = 'rejected', admin_note = 'Auto-rejected: item already returned', reviewed_at = NOW()
      WHERE item_id = :item_id AND status = 'pending' AND id <> :cid
    ");
    $st3->execute([':item_id' => (int)$claim['item_id'], ':cid' => $claimId]);
  }

  $pdo->commit();
}

function update_item_status(PDO $pdo, int $id, string $status): void {
  if (!in_array($status, ['open','matched','returned'], true)) return;
  $st = $pdo->prepare("UPDATE items SET status = :s WHERE id = :id");
  $st->execute([':s' => $status, ':id' => $id]);
}
