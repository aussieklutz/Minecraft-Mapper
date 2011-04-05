

$processname = "ngrep.exe";
While ProcessExists($processname)
ProcessClose($processname);
WEnd

If ( Not FileExists("MapUpdater.ini") ) Then
	MsgBox(0, "MapUpdater", "Welcome to MapUpdater" & @CRLF & "As this is your first time running MapUpdater, we need to ask you some questions to set you up.")
	$proc = Run("ngrep.exe /L", @WorkingDir, @SW_HIDE, 0x2)
	While(ProcessExists($proc))
	WEnd
	$interfaces = StdoutRead($proc)
	$interface = InputBox("MapUpdater", "Interfaces: " & @CRLF & $interfaces & @CRLF & "Primary Internet Interface Number", "1", "", 640, 320)
	$server = InputBox("MapUpdater", "MineCraft Server address", "")
	$mapserver = InputBox("MapUpdater", "Mapper Server address", "*server_address_here*/mapper.php")
	$email = InputBox("MapUpdater", "Email address")
	$delay = InputBox("MapUpdater", "Seconds between updates", "3")
	IniWrite("MapUpdater.ini", "Configuration", "NetworkInterface", $interface)
	IniWrite("MapUpdater.ini", "Configuration", "MinecraftServer", $server)
	IniWrite("MapUpdater.ini", "Configuration", "MapServer", $mapserver)
	IniWrite("MapUpdater.ini", "Configuration", "UpdateDelay", $delay)
	IniWrite("MapUpdater.ini", "User", "Email", $email)
	MsgBox(0, "MapUpdater", "Congratulations, your MapUpdater is ready to go. From now on it will run silently in the background.")
Else
	$interface = IniRead("MapUpdater.ini", "Configuration", "NetworkInterface", "")
	$server = IniRead("MapUpdater.ini", "Configuration", "MinecraftServer", "")
	$mapserver = IniRead("MapUpdater.ini", "Configuration", "MapServer", "")
	$delay = IniRead("MapUpdater.ini", "Configuration", "UpdateDelay", "")
	$email = IniRead("MapUpdater.ini", "User", "Email", "")
EndIf

TCPStartup()
$ip = TCPNameToIP($server)
TCPShutdown()

$listener = Run( "ngrep -Xxq -S 34 -d " & $interface & " 0b " & Chr(34) & "dst " & $ip & Chr(34) , @WorkingDir, @SW_HIDE, 0x2)
$output = ""
$x = 0
$z = 0

$inPacket = False
$foundPacket = False
$packetSize = 0
$packet = ""
While (ProcessExists($listener))
	$output = StdoutRead($listener)
	$lines = StringSplit($output, @LF, 2)
	$foundPacket = False
	;MsgBox(1, "", UBound($lines))
	For $line In $lines
		If(StringLeft($line, 1) == "T") Then
			; Start of packet
			$inPacket = True
			$packetSize = 0
			$packet = ""
			;MsgBox(1, "", "Packet Start")
		ElseIf(StringLen($line) >= 72) Then
			;MsgBox(1, "", "Packet Continues")
			If($inPacket) Then
				$contents = StringStripWS($line, 8)
				If ($packetSize < (32)) Then
					$packet &= StringLeft($contents, 32)
					$packetSize += 16
				Else
					$packet &= StringLeft($contents, 4)
					$packetSize += 2
				EndIf
			EndIf
		Else
			If($inPacket AND StringLeft($packet, 2) == "0b") Then
				;MsgBox(1, "", "Found")
				$foundPacket = True
				$sent = InetGet($mapserver & "?packet=" & $packet & "&email=" & $email, 'error.txt')
				;MsgBox(0, "", "got one")
			EndIf
			;MsgBox(1, "", "Packet End: " & $packet)
			$inPacket = False
			$packetSize = 0
			$packet = ""
		EndIf
		If($foundPacket) Then
			ExitLoop
		EndIf
	Next
	Sleep(1000 * $delay)
	
WEnd