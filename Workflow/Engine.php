<?php

namespace MxcDropshipIntegrator\Workflow;

class Engine
{


    //    'offen' =>
    //          if bezahlt {
    //              $orderType = 0;
    //              $error = 0;
    //
    //            if Lager-Artikel in der Bestellung {
    //                  $orderType |= ORDER_VAPEE;
    //                  $result = Verfügbarkeit der Produkte prüfen
    //                  if ($result == ok) {
    //                      Packzettel erzeugen
    //                      Option: DHL Versandlabel erzeugen
    //                  } else {
    //                      $error |= ERROR_OUTOFSTOCK_VAPEE
    //                      $problemListeLager = Produkte, die Probleme machen
    //                  }
    //              }
    //              if InnoCigs Artikel in der Bestellung {
    //                  $orderType |= ORDER_INNOCIGS;
    //                  Verfügbarkeit der Produkte prüfen
    //                  wenn verfügbar {
    //                      $result = Innocigs Dropship Auftrag schicken
    //                      wenn $result == not ok {
    //                          $error |= ERROR_OUTOFSTOCK_INNOCIGS
    //                          $problemListeInnocigs = Produkte, die Probleme machen
    //                      }
    //                  },
    //              }
    //              // kein Fehler aufgetreten
    //              if ($error == 0) {
    //                  Folgestatus = 'in Bearbeitung'
    //                  $mail an Kunden, 'In Bearbeitung'
    //                  // InnoCigs Bestellung
    //                  if ($orderType == 1) {
    //                      $adminMail = neue Bestellung, bereits bezahlt, erfolgreich weitergeleitet an InnoCigs
    //                  elseif ($
    //
    //              }
    //
    //
    //
}