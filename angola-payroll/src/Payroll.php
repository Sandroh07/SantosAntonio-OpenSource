<?php
declare(strict_types=1);
/** Monetary amounts use decimal strings at the database boundary. Calculations round to 2 decimals. */
final class Payroll {
    public static function money(mixed $value, bool $allowNegative=false): float {
        $v=trim((string)$value);
        if(!preg_match('/^-?\d{1,12}(?:\.\d{1,2})?$/D',$v)) throw new InvalidArgumentException('Money requires a dot decimal and at most two digits (e.g. 125000.00).');
        $n=(float)$v;
        if(!$allowNegative && $n<0)throw new InvalidArgumentException('Negative monetary amount is not allowed.');
        return round($n,2);
    }
    public static function period(string $input): string {
        if(!preg_match('/^20\d\d-(0[1-9]|1[0-2])$/D',$input)) throw new InvalidArgumentException('Use YYYY-MM period.');
        return $input;
    }
    public static function tax(float $taxable, array $brackets): float {
        if($taxable<=0)return 0.0;
        foreach($brackets as $b) {
            $lower=(float)$b['lower_bound'];
            $upper=isset($b['upper_bound']) ? (float)$b['upper_bound'] : null;
            if($taxable>$lower && ($upper===null || $taxable<=$upper)) {
                return round(max(0,(float)$b['fixed_amount']+($taxable-$lower)*(float)$b['rate']),2);
            }
            if($taxable<=$lower && $upper!==null && $lower===0.0) return 0.0;
        }
        throw new DomainException('No approved tax bracket covers this taxable amount.');
    }
    public static function calculate(array $e,array $brackets,float $employeeRate,float $employerRate): array {
        foreach(['base_salary','taxable_allowance','exempt_allowance'] as $field) {
            if(!isset($e[$field]) || !is_numeric($e[$field]) || (float)$e[$field]<0)throw new InvalidArgumentException('Invalid salary component '.$field);
        }
        if($employeeRate<0||$employeeRate>1||$employerRate<0||$employerRate>1)throw new InvalidArgumentException('Social rates must be 0..1.');
        $base=round((float)$e['base_salary'],2);
        $taxable=round((float)$e['taxable_allowance'],2);
        $exempt=round((float)$e['exempt_allowance'],2);
        $ssBase=isset($e['social_base'])&&$e['social_base']!==null ? (float)$e['social_base'] : $base;
        if($ssBase<0)throw new InvalidArgumentException('Social-security base must be nonnegative.');
        $gross=round($base+$taxable+$exempt,2);
        $employeeSS=round($ssBase*$employeeRate,2);
        $employerSS=round($ssBase*$employerRate,2);
        $irtBase=max(0,round($base+$taxable-$employeeSS,2));
        $irt=self::tax($irtBase,$brackets);
        $net=round($gross-$employeeSS-$irt,2);
        return ['base'=>$base,'taxable_allowance'=>$taxable,'exempt_allowance'=>$exempt,'social_base'=>$ssBase,'gross'=>$gross,'taxable_base'=>$irtBase,'inss_employee'=>$employeeSS,'inss_employer'=>$employerSS,'irt'=>$irt,'net'=>$net,'employer_cost'=>round($gross+$employerSS,2)];
    }
}
