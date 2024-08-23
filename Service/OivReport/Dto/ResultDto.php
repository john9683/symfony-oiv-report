<?php

namespace App\Service\OivReport\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class ResultDto extends DataTransferObject
{
  /** @var int */
  public $idRes;
  /** @var int */
  public $idHsp;
  /** @var int */
  public $dateOut;
  /** @var string|null */
  public $resArea;
  /** @var int */
  public $idDep;
  /** @var string */
  public $blankName;
  /** @var string */
  public $department;
  /** @var int */
  public $typeDep;
  /** @var int */
  public $dayStac;
  /** @var string|null */
  public $ward;
  /** @var string */
  public $doctor;
  /** @var string */
  public $patient;
  /** @var string|null */
  public $contingentHosp;
  /** @var string|null */
  public $contingentAmb;
  /** @var string|null */
  public $mkb;
  /** @var string|null */
  public $ds;
  /** @var string|null */
  public $conclusion;

  /**
   * @param array $result
   * @return ResultDto
   */
  public static function buildResultDto(array $result): ResultDto
  {
    return new ResultDto([
      'idRes' => $result['ID_RES'],
      'idHsp' => $result['ID_HSP'],
      'dateOut' => $result['DATE_OUT'],
      'resArea' => $result['RES_ARREA'] !== '' ? $result['RES_ARREA'] : 'Область не установлена',
      'idDep' => $result['ID_DEP'],
      'blankName' => $result['BLANK'],
      'department' => $result['DEPARTMENT'],
      'typeDep' => $result['TYPEDEP'],
      'dayStac' => $result['DAYSTAC'],
      'ward' => $result['WARD'],
      'doctor' => $result['DOCTOR'],
      'patient' => $result['PATIENT'],
      'contingentHosp' => $result['CONTINGENT_HOSP'],
      'contingentAmb' => $result['CONTINGENT_AMB'],
      'mkb' => $result['MKB'],
      'ds' => str_replace('&nbsp;', '', strip_tags($result['DS'])),
      'conclusion' => str_replace('&nbsp;', '', strip_tags($result['CONCL'])),
    ]);
  }
}


