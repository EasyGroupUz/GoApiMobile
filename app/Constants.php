<?php

namespace App;

class Constants {

   //   STATUS
      const ACTIVE = 1;
      const NOT_ACTIVE = 2;

   // Order Details   seates types
      const  PARTIALLY = 0;
      const FULL_PLEACE = 1;

   // Order    type_ids
   const ORDERED = 3;
   const ON_THE_WAY = 4;
   const COMPLETED = 5;
   const CANCEL_ORDER = 8;

   // Offer    type_ids
   const NEW = 6;
   const ACCEPT = 7;
   const CANCEL = 8;

   // Offer    create_type  and cancel_type
   const ORDER_DETAIL= 0;
   const ORDER = 1;

   const MIN_DESTINATION_PRICE = 300;
   const MAX_DESTINATION_PRICE = 500;

   // Driver doc_status
   const NOT_ACCEPT = 1;
   const ACCEPTED = 2;
   const WAITING = 3;
   const CENCELED = 4;
}