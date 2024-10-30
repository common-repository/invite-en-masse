<?php 

/*  
	Copyright 2009 Simon Wheatley

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

class BasecampToCSV
{
	
	protected $xml;
	protected $people;
	
	function __construct( $input_filename )
	{
		if ( ! file_exists( $input_filename ) ) throw new exception( "XML file ($input_filename) doesn't exist." );
		$this->xml = simplexml_load_file( $input_filename );
		$this->people = array();
	}

	public function export_to_file( $output_filename )
	{
		$this->parse_for_people();
		$handle = fopen( $output_filename, 'w' );
		foreach ( $this->people as & $person ) {
			$this->write_row( $person, $handle );
		}
	}
	
	protected function write_row( & $data, $handle )
	{
		if ( ! fputcsv( $handle, $data ) ) throw new exception( "Could not write the following data:\n" . print_r( $data, true ) );
	}
	
	protected function parse_for_people()
	{
		// First get the people from the firm which own's this Basecamp file
		$this->add_people_from( $this->xml->firm );
		// Now get the people in the client companies
		$num_clients = count( $this->xml->clients->client );
		for ( $i = 0; $i < $num_clients; $i++ ) {
			$client = & $this->xml->clients->client[ $i ];
			$this->add_people_from( $client );
		}
	}
	
	protected function add_people_from( & $org )
	{
		$num_people = count( $org->people->person );
		for ( $i = 0; $i < $num_people; $i++ ) {
			$person = & $org->people->person[ $i ];
			$this->add_person( $person );
		}
	}
	
	protected function add_person( & $person )
	{
		$record = array();
		foreach ( $person as $att => $val ) {
			// SO awkward and it must be wrong, but it works... 
			switch ( $att )
			{
				case 'first-name':
					$record[ 0 ] = (string) $val;
					break;
				case 'last-name':
					$record[ 1 ] = (string) $val;
					break;
				case 'email-address':
					$record[ 2 ] = (string) $val;
					break;
				case 'user-name':
					$record[ 3 ] = (string) $val;
					break;
			}
		}
		$this->people[] = $record;
	}
	
}

$basecamp_to_csv = new BasecampToCSV( 'basecamp.xml' );
$basecamp_to_csv->export_to_file( 'export.csv' );

?>