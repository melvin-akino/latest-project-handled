<?php
//Inputs
$stake = 100;
$price = 1.01;
$hdp = -2.75;
$bet_score = 2;
$against_score = 2;

//Computations based on inputs
$towin = $stake * $price;
$halfwin = $towin / 2;
$halflose = $stake / 2;


$bet_team_counter = 0;

while ($bet_team_counter <= 10) {
	
	//echo "Bet Team: $bet_team <br/>";
	$against_team_counter = 0;
	$table=[];

	while($against_team_counter <= 10) {
		//computes the difference score by adding he HDP and bet team counter minus the against team score counter
		$difference = ($hdp + $bet_team_counter) - $against_team_counter;

		//check the result, + = win/halfwin | -lose/halflose | draw or push
		if ($difference > 0) {
			switch($difference) {
				case 0.25 : $result = $halfwin; break;
				case 0.75 : $result = $halfwin; break;
				default : $result = $towin; break;
			}
			
			$color = "green";
		}
		else if ($difference < 0) {
			switch($difference) {
				case -0.25 : $result = ($halflose * -1); break;
				case -0.75 : $result = ($halflose * -1); break;
				default : $result = ($stake * -1); break;
			}
			$color = "red";
		}
		else {
			$result = "push";
			$color = "white";
		}
		
		if ($against_team_counter <= $against_score || $bet_team_counter <= $bet_score) {
			$color = "grey";
		}
		
		$table[$bet_team_counter][$against_team_counter] = $result . " - $color";
		
		$against_team_counter++;
	}
	$final_table[] = $table;
	$bet_team_counter++;
}
echo print_r(var_dump($final_table),true);