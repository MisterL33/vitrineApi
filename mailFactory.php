<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\FirePHPHandler;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers', 'Content-Type');
require './vendor/autoload.php';


$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$config['db']['host']   = 'localhost';
$config['db']['user']   = 'root';
$config['db']['pass']   = '';
$config['db']['dbname'] = 'vitrine';

$app = new \Slim\App(['settings' => $config]);
$container = $app->getContainer();

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$container['logger'] = function($c) {
    $logger = new Logger('my_logger');
    $logger->pushHandler(new StreamHandler(__DIR__.'/app.log', Logger::DEBUG));
    $logger->pushHandler(new FirePHPHandler());
    return $logger;
};



$app->post('/mail/', function ($request, $response, $args) {
    
    $requestbody = $request->getBody();
    $requestobject = json_decode($requestbody);
    $email = $requestobject->mail->address;
    $content = $requestobject->mail->message;

    $content = sprintf("%s <br> <br> (%s)", $content, $email);
    //$data = json_decode(key($data));
    //$email = $data->mail->address;
    //$content = $data->mail->message;


    $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
    try {
        //Server settings
        $mail->SMTPDebug = 2;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = 'juliennesoftware@gmail.com';                 // SMTP username
        $mail->Password = 'pomme974';                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                                    // TCP port to connect to
    
        //Recipients
        $mail->setFrom($email, 'Nouveau contact');
        $mail->addAddress('juliennesoftware@gmail.com', 'Nouveau contact');     // Add a recipient
        $mail->addReplyTo($email, 'Information');
    
        $mail->isHTML(true);                                 
        $mail->Subject = 'Nouveau contact';
        $mail->Body    = $content;
        
        $mail->send();
        echo 'Message has been sent';
        $this->logger->info('Message envoyé');
    } catch (Exception $e) {
        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
        $this->logger->info($mail->ErrorInfo);
        
    }
    return $response;
});



$app->run();
