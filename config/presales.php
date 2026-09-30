<?php
return ['enabled'=>(bool)env('PRESALES_ENABLED',false),'sms_enabled'=>(bool)env('PRESALES_SMS_ENABLED',false),'twilio_sid'=>env('TWILIO_ACCOUNT_SID'),'twilio_token'=>env('TWILIO_AUTH_TOKEN'),'twilio_from'=>env('TWILIO_FROM')];
