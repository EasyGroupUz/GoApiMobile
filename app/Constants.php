<?php

namespace App;

class Constants {

   //   STATUS
      const ACTIVE = 1;
      const NOT_ACTIVE = 2;

   // Order Details   seates types
      const PARTIALLY = 0;
      const FULL_PLEACE = 1;

   // Order price_type
      const CASH = 0;
      const CARD = 1;

   // Order    type_ids
      const ORDERED = 3;
      const ON_THE_WAY = 4;
      const COMPLETED = 5;
      const CANCEL_ORDER = 8;

   // Offer    type_ids
      const NEW = 6;
      const ACCEPT = 7;
      const CANCEL = 8;


   // Offer status for Order show
      const NEW_OFFER = 0;
      const ACCEPT_OFFER = 1;
      const NOT_OFFER = 2;
      const CANCELED_OFFER = 3;

   // Offer accepted
      const OFFER_ACCEPTED = 1;
      const NOT_ACCEPTED = 0;

   // Offer    create_type  and cancel_type
      const ORDER_DETAIL= 0;
      const ORDER = 1;

   const MIN_DESTINATION_PRICE = 100;
   const MAX_DESTINATION_PRICE = 350;

   // Driver doc_status
      const NOT_ACCEPT = 1;
      const ACCEPTED = 2;
      const WAITING = 3;
      const CENCELED = 4;

   // order_details type
      const CREATED_ORDER_DETAIL = 1;
      const SEARCHED_ORDER_DETAIL = 2;

   // users doc_status
      const NOT_ACCEPTED_USER = 0;
      const WAITING_ACCEPTING_USER = 1;
      const ACCEPTED_USER = 2;
      const ACCEPTED_USER_FIRST = 3;

   // cars type (0 - not accepter, 1 - accepted)
      const NOT_ACCEPTED_CAR = 0;
      const ACCEPTED_CAR = 1;
}