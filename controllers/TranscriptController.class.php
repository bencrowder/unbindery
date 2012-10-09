<?php

class TranscriptController {

	// --------------------------------------------------
	// Save transcript handler

	static public function save($params) {
		$item = $params['item'];
		$status = $params['status'];
		$type = $params['type'];
		$transcript = $params['transcript'];

		$auth = Settings::getProtected('auth');
		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		// Make sure user has access (member of project, item is in queue)
		// TODO: Finish

		// Update it if it exists; add it if it doesn't
		$transcriptObj = $user->loadTranscript($item, $type);
		if ($transcriptObj) {
			$user->updateTranscript($item, $status, $transcript->getText(), $type);
		} else {
			$user->addTranscript($item, $status, $transcript->getText(), $type);
		}
	}


	// --------------------------------------------------
	// Load transcript handler

	static public function load($params) {
		$item = $params['item'];
		$type = $params['type'];

		$auth = Settings::getProtected('auth');
		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		// Make sure user has access (member of project, item is in queue)
		// TODO: Finish

		return $user->loadTranscript($item, $type);
	}


	// --------------------------------------------------
	// Diff transcript handler

	static public function diff($params) {
		$transcripts = $params['transcripts'];
		$str = '';

		// TODO: expand to allow more than 2 transcripts
		// TODO: also check for spaces

		$transcriptA = $params['transcripts'][0];
		$transcriptB = $params['transcripts'][1];

		// Code from https://github.com/paulgb/simplediff/blob/5bfe1d2a8f967c7901ace50f04ac2d9308ed3169/simplediff.php
		$diff = self::__diff(explode(' ', $transcriptA['transcript']), explode(' ', $transcriptB['transcript']));

		foreach ($diff as $k) {
			if (is_array($k)) {
				$str .= (!empty($k['d']) ? "[@{$transcriptA['user']}]" . implode(' ', $k['d']) . "[/@{$transcriptA['user']}] " : '') . (!empty($k['i']) ? "[@{$transcriptB['user']}]" . implode(' ', $k['i']) . "[/@{$transcriptB['user']}] " : '');
			} else {
				$str .= $k . ' ';
			}
		}

		return $str;
	}

	// Code from https://github.com/paulgb/simplediff/blob/5bfe1d2a8f967c7901ace50f04ac2d9308ed3169/simplediff.php
	static public function __diff($old, $new) {
		$maxlen = 0;

		foreach ($old as $oindex => $ovalue) {
			$nkeys = array_keys($new, $ovalue);

			foreach ($nkeys as $nindex) {
				$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
				if ($matrix[$oindex][$nindex] > $maxlen) {
					$maxlen = $matrix[$oindex][$nindex];
					$omax = $oindex + 1 - $maxlen;
					$nmax = $nindex + 1 - $maxlen;
				}
			}	
		}

		if ($maxlen == 0) return array(array('d' => $old, 'i' => $new));

		return array_merge(
			self::__diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
			array_slice($new, $nmax, $maxlen),
			self::__diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
	}

}



?>
