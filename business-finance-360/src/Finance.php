<?php
declare(strict_types=1);
final class Finance {
 public static function money(mixed $input): float {
  $v=trim((string)$input);
  if(!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D',$v))throw new InvalidArgumentException('Enter a nonnegative amount with up to two decimal places.');
  return round((float)$v,2);
 }
 public static function month(string $date): string {
  if(!preg_match('/^20\d\d-(0[1-9]|1[0-2])$/D',$date))throw new InvalidArgumentException('Use a YYYY-MM month.');
  return $date;
 }
 public static function periodFromDate(string $date): string {
  if(!preg_match('/^20\d\d-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/D',$date))throw new InvalidArgumentException('Use YYYY-MM-DD.');
  $d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
  if(!$d||$d->format('Y-m-d')!==$date)throw new InvalidArgumentException('Invalid date.');
  return substr($date,0,7);
 }
 public static function projectMonth(array $entries,array $budget): array {
  $income=0.;$expense=0.;
  foreach($entries as $row){$amt=(float)$row['amount'];if($amt<0)throw new InvalidArgumentException('Negative amount is not allowed.');
   if($row['kind']==='income')$income+=$amt;elseif($row['kind']==='expense')$expense+=$amt;else throw new InvalidArgumentException('Invalid transaction kind.');
  }
  $income=round($income,2);$expense=round($expense,2);$allocated=round(array_sum(array_map('floatval',$budget)),2);
  return ['income'=>$income,'expenses'=>$expense,'net'=>round($income-$expense,2),'expense_budget'=>$allocated,'budget_remaining'=>round($allocated-$expense,2)];
 }
 public static function ensureUnlocked(PDO $db,string $month): void {
  $s=$db->prepare('SELECT is_locked FROM periods WHERE month=? FOR UPDATE');$s->execute([$month]);$v=$s->fetch();
  if($v&&(int)$v['is_locked']===1)throw new DomainException('This period is locked. Ask an administrator to reopen it after review.');
 }
 public static function accountBalance(PDO $db,int $id): float {
  $s=$db->prepare('SELECT opening_balance + COALESCE((SELECT SUM(CASE WHEN kind="income" THEN amount ELSE -amount END) FROM transactions WHERE account_id=a.id),0) + COALESCE((SELECT SUM(amount) FROM transfers WHERE target_account_id=a.id),0) - COALESCE((SELECT SUM(amount) FROM transfers WHERE source_account_id=a.id),0) AS balance FROM accounts a WHERE a.id=?');
  $s->execute([$id]);$r=$s->fetch();if(!$r)throw new DomainException('Account not found.');return round((float)$r['balance'],2);
 }
}
