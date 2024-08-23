<?php

namespace App\Controller\OivReport;

use App\Util\DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\OivReport\OivReportService;

/**
 * @Route("/oiv_report")
 */
class OivReportController extends AbstractController
{
  public function __construct(
    OivReportService $service
  ) {
    $this->service = $service;
  }

  /**
   * @Route("/oiv/html", methods={"GET"})
   */
  public function getSummaryReport(Request $request): Response
  {
    $dateFromDefault = (new DateTimeImmutable())::createFromTimestamp(strtotime(date('Y-m-d')))
      ->format('Y-m-01');
    $dateToDefault = (new DateTimeImmutable())::createFromTimestamp(strtotime(date('Y-m-d')))
      ->format('Y-m-d');

    $blank = trim($request->get('blank')) ?: 'all';
    $doctor = trim($request->get('doctor')) ?: 'all';
    $dateFrom = trim($request->get('from')) ?: $dateFromDefault;
    $dateTo = trim($request->get('to')) ?: $dateToDefault;

    $template = $this->render('oivReport/oiv_report_total.html.twig', [
      'groupName' => $this->service->getGroupName()['NAME'],
      'user' => $doctor !== 'all' ? $this->service->getUser()[$doctor] : 'все исполнители',
      'doctorArray' => $this->service->getDoctorArray(),
      'data' => $this->service->getOivArray($dateFrom, $dateTo, $blank, $doctor),
      'baseUrl' => $_SERVER['BASE_URL'],
    ]);

    $response = new Response($template->getContent());
    $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

    return $response;
  }

  /**
   * @Route("/oiv/json", methods={"GET"})
   */
  public function getSummaryReportJson(Request $request): Response
  {
    $dateFrom = trim($request->get('from'));
    $dateTo = trim($request->get('to'));

    return $this->json($this->service->getOivArray($dateFrom, $dateTo));
  }

  /**
   * @Route("/patient/html", methods={"GET"})
   */
  public function getPatientData(Request $request): Response
  {
    $dateFromDefault = (new DateTimeImmutable())::createFromTimestamp(strtotime(date('Y-m-d')))
      ->format('Y-m-01');
    $dateToDefault = (new DateTimeImmutable())::createFromTimestamp(strtotime(date('Y-m-d')))
      ->format('Y-m-d');

    $dateFrom = trim($request->get('from')) ?: $dateFromDefault;
    $dateTo = trim($request->get('to')) ?: $dateToDefault;

    $blank = trim($request->get('blank')) ?: 'all';
    $doctor = trim($request->get('doctor')) ?: 'all';

    $template = $this->render('oivReport/oiv_report_patient.html.twig', [
      'groupName' => $this->service->getGroupName()['NAME'],
      'user' => $doctor !== 'all' ? $this->service->getUser()[$doctor] : 'все исполнители',
      'blank' => $blank,
      'doctor' => $doctor,
      'blankArray' => $this->service->getBlankArray(),
      'doctorArray' => $this->service->getDoctorArray(),
      'data' => $this->service->getOivArray($dateFrom, $dateTo, $blank, $doctor),
      'baseUrl' => $_SERVER['BASE_URL'],
    ]);

    $response = new Response($template->getContent());
    $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

    return $response;
  }

  /**
   * @Route("/patient/json", methods={"GET"})
   */
  public function getPatientDataJson(Request $request): Response
  {
    $dateFrom = trim($request->get('from'));
    $dateTo = trim($request->get('to'));
    $blank = trim($request->get('blank'));

    return $this->json($this->service->getOivArray($dateFrom, $dateTo, $blank));
  }

  /**
   * @Route("/lab_journal/html", methods={"GET"})
   */
  public function getLabJournalData(Request $request): Response
  {
    $dateFromDefault = (new DateTimeImmutable())::createFromTimestamp(strtotime(date('Y-m-d')))
      ->format('Y-m-d');
    $dateToDefault = (new DateTimeImmutable())::createFromTimestamp(strtotime(date('Y-m-d')))
      ->format('Y-m-d');

    $dateFrom = trim($request->get('from')) ?: $dateFromDefault;
    $dateTo = trim($request->get('to')) ?: $dateToDefault;

    $data = [
      'dateFrom' => $dateFrom,
      'dateTo' => $dateTo,
      'labJournalName' => $this->service->getLabJournalName(),
      'parametersNames' => $this->service->getParametersNames(),
      'items' => $this->service->getDataForLabJournal($dateFrom, $dateTo),
    ];

    $template = $this->render('oivReport/oiv_report_lab_journal.html.twig', [
      'data' => $data,
      'baseUrl' => $_SERVER['BASE_URL'],
    ]);

    $response = new Response($template->getContent());
    $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

    return $response;
  }

  /**
   * @Route("/lab_journal/json", methods={"GET"})
   */
  public function getLabJournalDataJson(Request $request): Response
  {
    $dateFromDefault = (new DateTimeImmutable())::createFromTimestamp(strtotime(date('Y-m-d')))
      ->format('Y-m-d');
    $dateToDefault = (new DateTimeImmutable())::createFromTimestamp(strtotime(date('Y-m-d')))
      ->format('Y-m-d');

    $dateFrom = trim($request->get('from')) ?: $dateFromDefault;
    $dateTo = trim($request->get('to')) ?: $dateToDefault;

    $response = [
      'dateFrom' => $dateFrom,
      'dateTo' => $dateTo,
      'labJournalName' => $this->service->getLabJournalName(),
      'parametersNames' => $this->service->getParametersNames(),
      'items' => $this->service->getDataForLabJournal($dateFrom, $dateTo)
    ];

    return $this->json($response);
  }
}
