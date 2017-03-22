<?php

$email = new Email();
try {
    $email->validate()->send();
    echo 1;
}catch(Exception $e) {
    echo $e->getMessage();
}
exit;

class Email {
    
    private $clientEmail;
    private $name;
    private $subject;
    private $message;
    
    private $form = array(
        'email' => '',
        'name'  => '',
        'subject' => '',
        'message' => '',
    );
    
    public function __construct() {
    }
    
    public function validate() {
        $data = $_POST;
        if (empty($data['email']) || empty($data['name']) 
            || empty($data['subject']) || empty($data['message'])) {
            throw new Exception('Invalid Request', 400);
        }
        $this->form['email']   = $data['email'];
        $this->form['name']    = $data['name'];
        $this->form['subject'] = $data['subject'];
        $this->form['message'] = $data['message'];
        return $this;
    }
    
    public function send() {
        $headers = 'From: client@mobeons.com' . "\r\n" .
            'Reply-To: contact@mobeons.com' . "\r\n" .
            'X-Mailer: Mobens Intelligence';
        $to = "contact@mobeons.com";
        $subject = "Got A Lead on Mobeons Intelligence WebSite :)";
        $message = "Email: ".$this->form['email']."\r\n\r\n"
            . "Name: ".$this->form['name']."\r\n\r\n"
            . "Subject: ".$this->form['subject']."\r\n\r\n"
            . "Message: ".$this->form['message']."\r\n\r\n"
        ;
        
        echo mail($to, $subject, $message, $headers);
    }
    
    public function sendAck() {
        
    }
    
}
