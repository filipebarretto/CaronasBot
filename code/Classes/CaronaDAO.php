<?php 
	require_once "Connection.php";
	require_once "Carona.php";

    $diff1Day = new DateInterval('P1D');
    $diffDay = new DateInterval('PT25H30M');
    $diffHour = new DateInterval('PT30M');

    class CaronaDAO{

		const INSERT_QUERY_IDA = "insert into public.caroneiros (chat_id, user_id, username, travel_hour, spots, location, route) values (:chat_id, :user_id, :username, :travel_hour, :spots, :location, '0'::bit(1))";
		const INSERT_QUERY_VOLTA = "insert into public.caroneiros (chat_id, user_id, username, travel_hour, spots, location, route) values (:chat_id, :user_id, :username, :travel_hour, :spots, :location, '1'::bit(1))";

		const QUERY_CREATE_CARPOOL = "insert into public.caroneiros (chat_id, user_id, username, travel_hour, route) values (:chat_id, :user_id, :username, :travel_hour, :route::bit(1))";
		const QUERY_CREATE_CARPOOL_WITH_DETAILS = "insert into public.caroneiros (chat_id, user_id, username, travel_hour, spots, location, route) values (:chat_id, :user_id, :username, :travel_hour, :spots, :location, :route::bit(1))";

		const QUERY_UPDATE_CARPOOL = "update public.caroneiros set travel_hour = :travel_hour, spots = '', location = '' where chat_id = :chat_id and user_id = :user_id and route = :route::bit(1)";
		const QUERY_UPDATE_CARPOOL_WITH_DETAILS = "update public.caroneiros set travel_hour = :travel_hour, spots = :spots, location = :location where chat_id = :chat_id and user_id = :user_id and route = :route::bit(1)";
		const QUERY_UPDATE_SPOTS = "update public.caroneiros set spots = :spots  where chat_id = :chat_id and user_id = :user_id and route = :route::bit(1)";

		const QUERY_SEARCH = "select * from public.caroneiros where chat_id = :chat_id and user_id = :user_id and route = :route::bit(1) ORDER BY travel_hour ASC;";

		const LISTA_QUERY_IDA = "select * from public.caroneiros where chat_id = :chat_id and route = '0'::bit(1) ORDER BY travel_hour ASC;";
		const LISTA_QUERY_VOLTA = "select * from public.caroneiros where chat_id = :chat_id and route = '1'::bit(1) ORDER BY travel_hour ASC;";
	
		const QUERY_REMOVE_CARPOOL = "delete from public.caroneiros where chat_id = :chat_id and user_id = :user_id and route = :route::bit(1)";

		const REMOVE_QUERY_IDA = "delete from public.caroneiros where chat_id = :chat_id and user_id = :user_id and route = '0'::bit(1)";
		const REMOVE_QUERY_VOLTA = "delete from public.caroneiros where chat_id = :chat_id and user_id = :user_id and route = '1'::bit(1)";
	
        private $db;	
		
        public function __construct(){
            $this->db = new Database();
        }

		public function getListaIda($chat_id){
			$this->db->query(CaronaDAO::LISTA_QUERY_IDA);
			$this->db->bind(":chat_id", $chat_id);
			
			return $this->montaListaCaronas($this->db->resultSet());
		}
		
		public function getListaVolta($chat_id){
			$this->db->query(CaronaDAO::LISTA_QUERY_VOLTA);
			$this->db->bind(":chat_id", $chat_id);
			
			return $this->montaListaCaronas($this->db->resultSet());
		}

		public function updateSpots($chat_id, $user_id, $spots, $route) {
			$this->db->query(CaronaDAO::QUERY_UPDATE_SPOTS);
			$this->db->bind(":chat_id", $chat_id);
			$this->db->bind(":user_id", $user_id);
			$this->db->bind(":spots", $spots);
			$this->db->bind(":route", $route);

			$this->db->execute();
			error_log("Erro: " . $this->db->getError());
			
		}

		
        /*
         * CREATES A NEW CARPOOL ON A SPECIFIC CHAT, OFFERED BY A SINGLE USER
         * LINKED TO HIS USER NAME ON A SPECIFIC TIME EITHER GOING OR RETURNING
         */
        
		public function createCarpool($chat_id, $user_id, $username, $travel_hour, $route) {

			$travel_hour = $this->setStringTime($travel_hour);
            
            $expiration = $this->getExpirationTimestamp($travel_hour);
			error_log("createCarpool");
            
			
			$this->db->query(CaronaDAO::QUERY_SEARCH);
			$this->db->bind(":chat_id", $chat_id);
			$this->db->bind(":user_id", $user_id);
			$this->db->bind(":route", $route);

			error_log($chat_id);
			error_log($uer_id);

			$this->db->execute();

			if (count($this->db->resultSet()) == 0) {
				error_log("insterting new carpool going");
				$this->db->query(CaronaDAO::QUERY_CREATE_CARPOOL);
				$this->db->bind(":chat_id", $chat_id);
				$this->db->bind(":user_id", $user_id);
				$this->db->bind(":username", $username);
				$this->db->bind(":travel_hour", $travel_hour);
				$this->db->bind(":route", $route);
                $this->db->bind(":expiration", $expiration);

				$this->db->execute();
				error_log("Erro: " . $this->db->getError());

			} else {
				error_log("updating existing carpool going");
				$this->db->query(CaronaDAO::QUERY_UPDATE_CARPOOL);
				$this->db->bind(":chat_id", $chat_id);
				$this->db->bind(":user_id", $user_id);
				$this->db->bind(":travel_hour", $travel_hour);
				$this->db->bind(":route", $route);
                $this->db->bind(":expiration", $expiration);

				$this->db->execute();
				error_log("Erro: " . $this->db->getError());
			}


		}

        /*
         * CREATES A NEW CARPOOL ON A SPECIFIC CHAT, OFFERED BY A SINGLE USER
         * LINKED TO HIS USER NAME ON A SPECIFIC TIME EITHER GOING OR RETURNING
         * WITH LOCATION AS REFERENCE AND NUMBER OF SPOTS
         */
        
		public function createCarpoolWithDetails($chat_id, $user_id, $username, $travel_hour, $spots, $location, $route) {

			error_log("create carpool with details");

			$travel_hour = $this->setStringTime($travel_hour);
			
			$this->db->query(CaronaDAO::QUERY_SEARCH);
			$this->db->bind(":chat_id", $chat_id);
			$this->db->bind(":user_id", $user_id);
			$this->db->bind(":route", $route);

			$this->db->execute();

			if (count($this->db->resultSet()) == 0) {
				error_log("insterting new carpool with details going");
				$this->db->query(CaronaDAO::QUERY_CREATE_CARPOOL_WITH_DETAILS);
				$this->db->bind(":chat_id", $chat_id);
				$this->db->bind(":user_id", $user_id);
				$this->db->bind(":username", $username);
				$this->db->bind(":travel_hour", $travel_hour);
				$this->db->bind(":spots", $spots);
				$this->db->bind(":location", strtolower($location));
				$this->db->bind(":route", $route);

				$this->db->execute();
				error_log("Erro: " . $this->db->getError());

			} else {
				error_log("updating existing carpool with details going");

				$this->db->query(CaronaDAO::QUERY_UPDATE_CARPOOL_WITH_DETAILS);
				$this->db->bind(":chat_id", $chat_id);
				$this->db->bind(":user_id", $user_id);
				$this->db->bind(":travel_hour", $travel_hour);
				$this->db->bind(":spots", $spots);
				$this->db->bind(":location", $location);
				$this->db->bind(":route", $route);

				$this->db->execute();
				error_log("Erro: " . $this->db->getError());
			}


		}


		public function removeCarpool($chat_id, $user_id, $route) {
			$this->db->query(CaronaDAO::QUERY_REMOVE_CARPOOL);
			$this->db->bind(":chat_id", $chat_id);
			$this->db->bind(":user_id", $user_id);
			$this->db->bind(":route", $route);
			
			$this->db->execute();
			error_log("Erro: " . $this->db->getError());
		}

        private function getExpirationTimestamp($travel_hour) {

            $today = date("Y-m-d");

            $timezone = date_default_timezone_get();
            $now = date_create(date("Y-m-d G:i P"));
            $nowTimestamp = $now->getTimestamp();

            $hour = explode(":", $travel_hour)[0];
            $minutes = explode(":", $travel_hour)[1];


            $carpoolExpiration = date_create($today . " " . $hour . ":" . $minutes, timezone_open('America/Sao_Paulo'));
            $carpoolExpirationTimestamp = $carpoolExpiration->getTimestamp();
            

            /*
             * CHECKS IF CARPOOL EXPIRATION IS ON SOME SAME
             * DAY OR THE NEXT DAY AND
             * SETS CARPOOL EXPIRATION TIME
             */
            if ($nowTimestamp > $carpoolExpirationTimestamp) {
                $carpoolExpiration->add($diffDay);
            } else {
                $carpoolExpiration->add($diffHour);
            }

            $carpoolExpirationTimestamp = $carpoolExpiration->getTimestamp();
            
            return $carpoolExpirationTimestamp;
        }
		
		private function acertarStringHora($travel_hour){
			return $travel_hour .= ":00";
		}


		private function setStringTime($travel_hour){
			return $travel_hour .= ":00";
		}
		
		private function montaListaCaronas($resultSet){

			error_log("montaListaCaronas");

			$resultado = array();
			
			foreach ($resultSet as $entrada)
			{
				array_push($resultado, new Carona($entrada));
			}
			
			return $resultado;
		}
    }

    
