<?php

namespace App\Service\OivReport;

use App\Service\OivReport\Dto\ResultDto;
use function Amp\Iterator\merge;

class OivReportService
{
  /**
   * @var DataProvider
   */
  public $dataProvider;

  public function __construct(DataProvider $dataProvider)
  {
    $this->dataProvider = $dataProvider;
  }

  /**
   * @return array
   */
  public function getGroupName(): array
  {
    return $this->dataProvider->getGroupName();
  }

  /**
   * @return array
   */
  public function getBlankArray(): array
  {
    return $this->dataProvider->getBlankArray();
  }

  /**
   * @return array
   */
  public function getDoctorArray(): array
  {
    return $this->dataProvider->getDoctorArray();
  }

  /**
   * @return array
   */
  public function getUser(): array {
    $doctorArray = $this->getDoctorArray();
    $userArray = [];
    foreach ($doctorArray as $user) {
      $userArray = $userArray + [$user['ID_USER'] => $user['DOCTOR']];
    }

    return $userArray;
  }

  /**
   * @param string $from
   * @param string $to
   * @param string $blank
   * @param string $doctor
   * @return array
   */
  public function getOivArray(string $from, string $to, string $blank = 'all', string $doctor = 'all'): array
  {
    if ($doctor === 'all') {
      $resultArray = $this->dataProvider->getResultData($from, $to, $blank);
    } else {
      $resultArray = $this->dataProvider->getResultDataByDoctor($from, $to, $blank, $doctor);
    }

    $total = 0;
    $amb = 0;
    $day = 0;
    $hosp = 0;

    $resultDtoArray = [];

    foreach ($resultArray as $result) {
      $resultDto = ResultDto::buildResultDto($result);

      if ($resultDto->typeDep === 9) {
        $resultDtoArray[$resultDto->blankName]['area'][$resultDto->resArea]['amb'][] = $resultDto;
        $resultDtoArray[$resultDto->blankName]['resCount']['amb'] += 1;
        ++$amb;
      } elseif ($resultDto->dayStac === 1) {
        $resultDtoArray[$resultDto->blankName]['area'][$resultDto->resArea]['day'][] = $resultDto;
        $resultDtoArray[$resultDto->blankName]['resCount']['day'] += 1;
        ++$day;
      } else {
        $resultDtoArray[$resultDto->blankName]['area'][$resultDto->resArea]['hosp'][] = $resultDto;
        $resultDtoArray[$resultDto->blankName]['resCount']['hosp'] += 1;
        ++$hosp;
      }
      $resultDtoArray[$resultDto->blankName]['resCount']['total'] += 1;
      ++$total;
    }

    $totalCount = [
      'total' => $total,
      'amb' => $amb,
      'day' => $day,
      'hosp' => $hosp
    ];

    return [
      'groupName' => $this->dataProvider->getGroupName()['NAME'],
      'dateFrom'=> $from,
      'dateTo' => $to,
      'totalCount' => $totalCount,
      'totalResult' => $resultDtoArray
    ];
  }

  /**
   * @return string
   */
  public function getLabJournalName(): string
  {
    return $this->dataProvider->getLabJournalName();
  }

  /**
   * @return array
   */
  public function getParametersNames(): array
  {
    $parametersArray = $this->dataProvider->getParametersNames();
    $columnsNames = [];

    foreach ($parametersArray as $parameter) {
      if (!in_array($parameter['ID_PAR'], $this->dataProvider->getConfig()['idParametersBan'])) {
        $columnsNames[] = $parameter['NAME'];
      }
    }

    return $columnsNames;
  }

  /**
   * @param string $from
   * @param string $to
   * @return array
   */
  public function getDataForLabJournal(string $from, string $to): array
  {
    $data = $this->dataProvider->getDataForLabJournal($from, $to);
    $parametersNames = $this->getParametersNames();

    $resultArray = [];
    foreach ($data as $row) {
      $resultArray[$row['ID_RES']][] = $row;
    }

    $data = [];
    foreach ($resultArray as $result) {
        $data[$result[0]['ID_RES']]['meta'] = [
            'idRes' => $result[0]['ID_RES'],
            'idPat' => $result[0]['ID_PAT'],
            'dateOut' => $result[0]['DATE_OUT'],
            'patient' => $result[0]['PATIENT'],
            'depart' => $result[0]['DEPART'],
        ];

        foreach ($parametersNames as $name) {
          $data[$result[0]['ID_RES']]['results'][$name] = '';

          foreach ($result as $item) {
          if ($name === $item['NAME']) {
            $data[$result[0]['ID_RES']]['results'][$name] = substr_replace(trim($item['VALUE_DIGI']), '', strpos(trim($item['VALUE_DIGI']), '.') + 1 + 2);
          }
        }
      }
    }

    return $data;
  }
}

