<?php
declare(strict_types=1);
require __DIR__.'/../src/Support.php';
assert_options(ASSERT_ACTIVE,1); assert_options(ASSERT_EXCEPTION,1);
function eq(mixed $a,mixed $b): void { if($a!==$b)throw new RuntimeException("Expected ".var_export($b,true).", got ".var_export($a,true)); }
eq(qty_to_milli('12.350'),12350);
eq(qty_to_milli('0.005'),5);
eq(qty_to_milli('-2.125',true),-2125);
eq(milli_to_qty(-11009),'-11.009');
eq(milli_to_qty(0),'0.000');
eq(valid_date('2026-11-05'),'2026-11-05');
foreach (['0.0001','1e4','abc','1,22'] as $bad) { try { qty_to_milli($bad);throw new RuntimeException('Allowed invalid value '.$bad); }catch(InvalidArgumentException $ignored){} }
try {qty_to_milli('-1');throw new RuntimeException('Negative not rejected');}catch(InvalidArgumentException $ignored){}
echo "Domain tests passed.
";
