<?php
/**
 * @author      Michael Vinogradov <interface.master@gmail.com>
 * @copyright   Copyright (c) Michael Vinogradov
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
namespace MRCT\Repositories;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

use MRCT\DatabaseManager;
use MRCT\Entities\AccessTokenEntity;
use MRCT\Entities\UserEntity;

class UserRepository implements UserRepositoryInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getUserEntityByUserCredentials(
		$username,
		$password,
		$grantType,
		ClientEntityInterface $clientEntity
	) {
		// Retrieve record from the db
		$db = DatabaseManager::getInstance();

		$obj = new \stdClass();
		$obj->email = $username;
		$obj->hash = $password;
		$record = $db->getUserByLogin( $obj->email, $obj->hash );

		if( $record !== false ) {
			return new UserEntity( $record->id );
		} else {
			return false;
		}

		// $obj->access_token = $tokenJWT->__toString(); // this is the token that's sent back to the user
		// $obj->id_student = $tokenJWT->getClaim('sub', false); // this is the student id
		// $obj->date_expires = date( "Y-m-d H:i:s", $tokenJWT->getClaim('exp', false) ); // this is when the token expires

		// $output = $db->insertOne( 'tokens', ['email'=>$obj->email], $obj ); // table,filter,object
		// return $output;


		// search student_accounts for this login
		// $tbl_accounts = $wpdb->prefix . 'ps_student_accounts';
		// $sql = "SELECT * FROM $tbl_accounts WHERE `username`=%s AND `password`=%s";
		// $query = $wpdb->prepare( $sql, $username, $password );
		// $row = $wpdb->get_row( $query );
		// // if found return user id
		// if( isset($row) ) {
		// 	return new UserEntity( $row->id_student );
		// }

		// // search group accounts for this login
		// $tbl_accounts = $wpdb->prefix . 'ps_course_characters';
		// $sql = "SELECT * FROM $tbl_accounts
		// 	WHERE LCASE(CONCAT(`name`,`id_course`)) = LCASE(%s)
		// 	AND LCASE(`pass`) = LCASE(%s)";
		// $query = $wpdb->prepare( $sql, $username.$cid, $password );
		// $row = $wpdb->get_row( $query );
		// if( isset($row) ) {
		// 	return new UserEntity( $row->id_character );
		// }

		// TODO: remove above this line !!

		// otherwise return nothing
		return;
	}
}
