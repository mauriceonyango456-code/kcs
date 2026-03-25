<?php
declare(strict_types=1);

namespace KCS\Models;

use KCS\Core\Database;
use PDO;

class FinancialModel
{
  public static function getCurrentBalance(int $studentId): float
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT balance
      FROM financial_records
      WHERE student_id = ? AND is_current = 1
      LIMIT 1
    ');
    $stmt->execute([$studentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      // If no current financial record exists, treat it as "not cleared" to satisfy
      // the gate: balance > 0 => approval denied.
      return 1.0;
    }
    return (float)$row['balance'];
  }

  public static function setCurrentFinancialRecord(
    int $studentId,
    float $feeAmount,
    float $amountPaid,
    float $balance,
    string $academicYear,
    string $termName
  ): void {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      INSERT INTO financial_records
        (student_id, academic_year, term_name, fee_amount, amount_paid, balance, is_current)
      VALUES (?, ?, ?, ?, ?, ?, 1)
      ON DUPLICATE KEY UPDATE
        academic_year = VALUES(academic_year),
        term_name = VALUES(term_name),
        fee_amount = VALUES(fee_amount),
        amount_paid = VALUES(amount_paid),
        balance = VALUES(balance),
        is_current = 1
    ');
    $stmt->execute([
      $studentId,
      $academicYear,
      $termName,
      $feeAmount,
      $amountPaid,
      $balance,
    ]);
  }
}

