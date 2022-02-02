<?php
require_once("library/util/db.php");

/**
 * Approve member.
 * Side effects:
 * - log event
 * - send the new member a confirmation email
 * @param string $phone for the new member
 * @return void
 */
function approve_member(string $phone): void
{
    if (!$phone) {
        throw new InvalidArgumentException("phone is required");
    }
    // approve member
    {
        $db = new DB("member");
        $db->prepare("UPDATE member SET approved_date=NOW() WHERE phone=?");
        $db->bind_param("s", $phone);
        $db->execute();
    }
    $email = "";
    // log success
    {
        $db = new DB("member");
        $db->prepare("SELECT first_name, surname, email FROM member WHERE phone = ?");
        $db->bind_param("s", $phone);
        $db->execute();
        $first_name = "";
        $surname = "";
        $db->stmt->bind_result($first_name, $surname, $email);
        $db->fetch();
        log::message("Info: New member $first_name $surname approved", __FILE__, __LINE__);
    }
    // send email
    {
        $subject = "NTNUI Swimming - membership approved";

        $headers = "Confirmation of registration<br>";
        $headers .= "<br>";
        $headers .= "Congratulations, you have now a valid membership in NTNUI Swimming<br>";
        $headers .= "Find information by visiting <a href='https://ntnui.slab.com/posts/welcome-to-ntnui-swimming-%F0%9F%92%A6-44w4p9pv'>this</a> link:<br>";
        $headers .= "<br>";
        $headers .= "<br>";
        $headers .= "Love from NTNUI Swimming";

        // send mail
        $mail_send_success = mail($email, $subject, $headers, "Content-type: text/html; charset=utf-8");
        if (!$mail_send_success){
            // throw new Exception("Could not send email");
            // local testing cannot (and shouldn't) send random emails
            log::message("[Warning]: failed sending approval mail", __FILE__, __LINE__);
        }
    }
}
