<?php

namespace App\Service\OivReport;

use App\Util\IDB;

class DataProvider
{
  /** @var IDB */
  protected $db;

  public function __construct(IDB $db)
  {
    $this->db = $db;
  }

  private const SQL_GROUP_NAME = <<<'SQL'
SELECT TRIM(NAME) AS NAME FROM GROUP_ANAL WHERE ID_GROUP = %s
SQL;

  private const SQL_BLANK_NAME = <<<'SQL'
SELECT ID_ANAL, NAME 
FROM ANALYSIS 
WHERE ID_GROUP = %s 
  AND ORDER_IN_NAZ <> -1 AND OLD_MARK IS NULL ORDER BY NAME
SQL;

  private const SQL_ID_ANAL = <<<'SQL'
SELECT ID_ANAL FROM ANALYSIS WHERE ID_GROUP = %s
SQL;

  private const SQL_DOCTOR = <<<'SQL'
SELECT DISTINCT u.ID_USER,
    (TRIM(u.FAM) || ' ' || SUBSTRING(TRIM(u.NAM) FROM 1 FOR 1) || '.' || SUBSTRING(TRIM(u.OTS) FROM 1 FOR 1) || '.') AS DOCTOR
FROM RESULTS r
    JOIN ANALYSIS a ON a.ID_ANAL = r.ID_ANAL AND a.ID_GROUP = %s
    JOIN USERS u ON u.ID_USER = r.ID_USER_INP
WHERE r.DATE_OUT > %s
ORDER BY DOCTOR
SQL;

  private const SQL_RESULT_DATA = <<<'SQL'
SELECT r.ID_RES, r.ID_HSP, r.DATE_OUT, r.RES_ARREA, r.ID_DEP,
a.NAME AS BLANK,
TRIM(d.NAME) AS DEPARTMENT, d.DTYPE AS TYPEDEP, d.PRIZ_D_STAC AS DAYSTAC,
w.WARD,
(TRIM(u.FAM) || ' ' || SUBSTRING(TRIM(u.NAM) FROM 1 FOR 1) || '.' || SUBSTRING(TRIM(u.OTS) FROM 1 FOR 1) || '.') AS DOCTOR, 
(TRIM(p.FAM) || ' ' || TRIM(p.NAM) || ' ' || TRIM(p.OTS)) AS PATIENT,
TRIM(c1.NAME) AS CONTINGENT_HOSP, TRIM(c2.NAME) AS CONTINGENT_AMB,
dh.MKB,
TRIM(m.DS) AS DS,
TRIM(rs.ISVALUE) AS CONCL
FROM RESULTS r
JOIN ANALYSIS a ON a.ID_ANAL = r.ID_ANAL
JOIN DEPART d ON d.ID = r.ID_DEP
LEFT JOIN WARDS w ON w.ID_WARD = (SELECT MAX(ID_WARD) FROM AMB_HSP WHERE ID_HSP = r.ID_HSP)
JOIN USERS u ON u.ID_USER = r.ID_USER_INP %s
JOIN PAT_LIST p ON p.ID_PAT = r.ID_PAT
JOIN HOSP h ON h.ID_HSP = r.ID_HSP
JOIN PASSPORT pass ON pass.ID_PAT = r.ID_PAT  
LEFT JOIN CONTINGENT c1 ON c1.ID = h.CONTING
LEFT JOIN CONTINGENT c2 ON c2.ID = pass.CONTING
LEFT JOIN DEP_HSP dh ON dh.ID_HSP = r.ID_HSP
    AND dh.ID_DEPHSP = (SELECT MAX(ID_DEPHSP) FROM DEP_HSP WHERE ID_HSP = r.ID_HSP AND MKB IS NOT NULL AND ID_DEP = r.ID_DEP)
LEFT JOIN MEASUR m ON m.ID_HSP = r.ID_HSP 
    AND m.ID_MSR = (SELECT MAX(ID_MSR) FROM MEASUR WHERE ID_HSP = r.ID_HSP AND DS IS NOT NULL)
JOIN RES_CONCL rs ON rs.ID_RES = r.ID_RES
    AND r.ID_RES = (SELECT MAX(ID_RES) FROM RES_CONCL WHERE ID_RES = r.ID_RES)
WHERE r.ID_ANAL = ?
AND r.DATE_OUT BETWEEN ? AND ?
ORDER BY BLANK, r.RES_ARREA, DEPARTMENT, DOCTOR  
SQL;

  private const SQL_DATA_FOR_LAB_JOURNAL = <<<'SQL'
SELECT r.ID_RES, r.ID_PAT, r.DATE_OUT,
    (TRIM(p.FAM) || ' ' || TRIM(p.NAM) || ' ' || TRIM(p.OTS)) AS PATIENT,
    TRIM(d.NAME) AS DEPART,
    par.NAME,
    res.VALUE_DIGI
FROM RESULTS r
    JOIN PAT_LIST p ON r.ID_PAT = p.ID_PAT
    JOIN DEPART d ON r.ID_DEP = d.ID
    JOIN PARAMETRS par ON r.ID_ANAL = par.ID_ANAL
    JOIN RES_PAR res ON par.ID_PAR = res.ID_PAR AND r.ID_RES = res.ID_RES
WHERE r.ID_ANAL = %s
    AND r.DATE_OUT BETWEEN ? AND ?
ORDER BY r.DATE_OUT, PATIENT
SQL;

  private const SQL_PARAMETERS_NAMES = <<<'SQL'
SELECT ID_PAR, NAME
FROM PARAMETRS
WHERE ID_ANAL = %s
    AND OLD_MARK IS NULL
ORDER BY ID_PAR
SQL;

  private const SQL_LAB_JOURNAL_NAME = <<<'SQL'
SELECT TRIM(SHORTNAME) AS SHORTNAME
FROM ANALYSIS
WHERE ID_ANAL = %s
SQL;

  /**
   * @return array
   */
  public function getConfig(): array
  {
    $jsonString = file_get_contents(__DIR__ .'/config.json');
    return json_decode($jsonString, true);
  }

  /**
   * @return array
   */
  public function getGroupName(): array
  {
    $sql = sprintf(self::SQL_GROUP_NAME, $this->getConfig()['oivCodeUzi']);

    return $this->db->row($sql);
  }

  /**
   * @return array
   */
  public function getBlankArray(): array
  {
    $sql = sprintf(self::SQL_BLANK_NAME, $this->getConfig()['oivCodeUzi']);

    return $this->db->rows($sql);
  }

  /**
   * @return array
   */
  public function getDoctorArray(): array
  {
    $sql = sprintf(self::SQL_DOCTOR, $this->getConfig()['oivCodeUzi'], $this->getConfig()['timeStartForGetDoctorSelect']);

    return $this->db->rows($sql);
  }

  /**
   * @param string $from
   * @param string $to
   * @param string $blank
   * @return array
   */
  public function getResultData(string $from, string $to, string $blank): array
  {
    $fromDti = new \DateTimeImmutable($from);
    $toDti = new \DateTimeImmutable($to . ' 23:59:59');
    $from = $fromDti->getTimestamp();
    $to = $toDti->getTimestamp();

    $totalResult = [];
    $sql = sprintf(self:: SQL_RESULT_DATA, '');

    if ($blank !== 'all') {
      $totalResult = $this->db->rows($sql, [$blank, $from, $to]);
    } else {
      $sqlCodeUzi = sprintf(self::SQL_ID_ANAL, $this->getConfig()['oivCodeUzi']);
      $idAnalArray = $this->db->col($sqlCodeUzi);
      foreach ($idAnalArray as $item => $blank) {
          $totalResult = array_merge($totalResult, $this->db->rows($sql, [$blank, $from, $to]));
      }
    }

    return $totalResult;
  }

  /**
   * @param string $from
   * @param string $to
   * @param string $blank
   * @return array
   */
  public function getResultDataByDoctor(string $from, string $to, string $blank, string $doctor): array
  {
    $fromDti = new \DateTimeImmutable($from);
    $toDti = new \DateTimeImmutable($to . ' 23:59:59');
    $from = $fromDti->getTimestamp();
    $to = $toDti->getTimestamp();
    $idUser = (int)$doctor;

    $totalResult = [];
    $sql = sprintf(self:: SQL_RESULT_DATA, ' AND u.ID_USER = '. $idUser);

    if ($blank !== 'all') {
      $totalResult = $this->db->rows($sql, [$blank, $from, $to]);
    } else {
      $sqlCodeUzi = sprintf(self::SQL_ID_ANAL, $this->getConfig()['oivCodeUzi']);
      $idAnalArray = $this->db->col($sqlCodeUzi);
      foreach ($idAnalArray as $item => $blank) {
        $totalResult = array_merge($totalResult, $this->db->rows($sql, [$blank, $from, $to]));
      }
    }

    return $totalResult;
  }

  /**
   * @param string $from
   * @param string $to
   * @return array
   */
  public function getDataForLabJournal(string $from, string $to): array
  {
    $from = new \DateTimeImmutable($from . ' 00:00:01');
    $to = new \DateTimeImmutable($to . ' 23:59:59');
    $from = $from->getTimestamp();
    $to = $to->getTimestamp();

    $sql = sprintf(self::SQL_DATA_FOR_LAB_JOURNAL, $this->getConfig()['oivCodeLabJournalOak']);

    return $this->db->rows($sql, [$from, $to]);
  }

  /**
   * @return array
   */
  public function getParametersNames(): array
  {
    $sql = sprintf(self::SQL_PARAMETERS_NAMES, $this->getConfig()['oivCodeLabJournalOak']);

    return $this->db->rows($sql);
  }

  /**
   * @return string
   */
  public function getLabJournalName(): string
  {
    $sql = sprintf(self::SQL_LAB_JOURNAL_NAME, $this->getConfig()['oivCodeLabJournalOak']);

    return $this->db->cell($sql);
  }
}
