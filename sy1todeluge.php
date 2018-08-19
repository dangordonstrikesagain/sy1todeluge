<?php
/*-------------------------------------------------------------
sy1 to deluge converter

usage from command line:

php sy1todeluge.php <inputfile> [outputFileNumber]

inputfile can take wildcards, but i have found on the ubuntu for windows
you need to put the wildcard filename in quotes, i.e.

php sy1todeluge.php "*.sy1" 600

otherwise it causes problems.


((((really not finished yet))))

have a look from line 209 onwards to see the conversions going on

---------------------------------------------------*/

$version=0;
echo "SY1 to Deluge converter version $version\r\n";

if ($argc<2) {
    echo "parameters: inputFileSpec [delugeOutputFileNumberStart]\r\n";
    exit;
}
$inputFiles = $argv[1];
$outputFileNumber = (int) ($argv[2] ?? 500);
$outputFileChar = 0;

$sy1Values=[];
// ------------------------------------
//
// deluge default values

$deluge=[];
$deluge['osc1_type'] = "saw";
$deluge['osc1_transpose'] = 0;
$deluge['osc1_cents'] = 0;
$deluge['osc1_retrigPhase'] = -1;
$deluge['osc2_type'] = "square";
$deluge['osc2_transpose'] = -12;
$deluge['osc2_cents'] = 0;
$deluge['osc2_retrigPhase'] = -1;
$deluge['osc2_oscillatorSync'] = 0;
$deluge['polyphonic'] = 1;
$deluge['clippingAmount'] = 0;
$deluge['voicePriority'] = 1;
$deluge['lfo1_type'] = "sine";
$deluge['lfo1_syncLevel'] = 0;
$deluge['lfo2_type'] = "sine";
$deluge['unison_num'] = 4;
$deluge['unison_detune'] = 50;
$deluge['compressor_syncLevel']  = 6;
$deluge['compressor_attack'] =327244;
$deluge['compressor_release'] = 936;
$deluge['mode'] = "subtractive";
$deluge['transpose'] = 0;
$deluge['num'] = 4;
$deluge['detune'] = 10;
$deluge['delay'] =
$deluge['pingPong'] = 1;
$deluge['analog'] = 1;
$deluge['syncLevel'] = 7;
$deluge['lpfMode'] = "24dB";
$deluge['modFXType'] = "none";
$deluge['arpeggiatorGate'] = '0x00000000';
$deluge['portamento'] = '0x80000000';
$deluge['compressorShape'] = '0xDC28F5B2';
$deluge['oscAVolume'] = '0x7FFFFFFF';
$deluge['oscAPulseWidth'] = '0x00000000';
$deluge['oscBVolume'] = '0x47AE1457';
$deluge['oscBPulseWidth'] = '0x00000000';
$deluge['noiseVolume'] = '0x80000000';
$deluge['volume'] = '0x50000000';
$deluge['pan'] = '0x00000000';
$deluge['lpfFrequency'] = '0x10000000';
$deluge['lpfResonance'] = '0xA2000000';
$deluge['hpfFrequency'] = '0x80000000';
$deluge['hpfResonance'] = '0x80000000';
$deluge['envelope1_attack'] = '0x80000000';
$deluge['envelope1_decay'] = '0xE6666654';
$deluge['envelope1_sustain'] = '0x7FFFFFFF';
$deluge['envelope1_release'] = '0x851EB851';
$deluge['envelope2_attack'] = '0xA3D70A37';
$deluge['envelope2_decay'] = '0xA3D70A37';
$deluge['envelope2_sustain'] = '0xFFFFFFE9';
$deluge['envelope2_release'] = '0xE6666654';
$deluge['lfo1Rate'] = '0x1999997E';
$deluge['lfo2Rate'] = '0x00000000';
$deluge['modulator1Amount'] = '0x80000000';
$deluge['modulator1Feedback'] = '0x80000000';
$deluge['modulator2Amount'] = '0x80000000';
$deluge['modulator2Feedback'] = '0x80000000';
$deluge['carrier1Feedback'] = '0x80000000';
$deluge['carrier2Feedback'] = '0x80000000';
$deluge['modFXRate'] = '0x00000000';
$deluge['modFXDepth'] = '0x00000000';
$deluge['delayRate'] = '0x00000000';
$deluge['delayFeedback'] = '0x80000000';
$deluge['reverbAmount'] = '0x80000000';
$deluge['arpeggiatorRate'] = '0x00000000';

$defaultDeluge = $deluge;

// --------------- conversion tables

$mapOscShape= [
        '0'=>'sine',
        '1'=>'saw',
        '2'=>'square',
        '3'=>'triangle'
    ];


// -------------------------------------
function scaleValue( $sy1Index, $syMin, $syMax, $delugeName, $dMin, $dMax, $gamma=1.0) {
    global $deluge, $sy1Values;
    if (!isset($sy1Values[$sy1Index])) {
        echo "\r\nsy1 parameter number $sy1Index was not found in this file";
        return;
    }
    $inRange = $syMax-$syMin;
    $outRange = $dMax-$dMin;
    $scaledIn = ($sy1Values[$sy1Index] - $syMin) / $inRange;     // this returns us input in range 0..1
    if ($gamma!==1.0) {
        $scaledIn = pow( $scaledIn, $gamma );
    }
    $deluge[$delugeName] =(int) ($scaledIn * $outRange + $dMin);
}

function scaleValueHex( $sy1Index, $syMin, $syMax, $delugeName, $dMin, $dMax, $gamma=1.0) {
    scaleValue($sy1Index, $syMin, $syMax, $delugeName, $dMin, $dMax,$gamma);
    $deluge[$delugeName] = '0x'.dechex((float)$deluge[$delugeName]);
}


//------------------------------
function indexToString( $sy1Index, $delugeName, $map ) {
    global $deluge, $sy1Values;
    if (!isset($sy1Values[$sy1Index])) {
        echo "\r\nsy1 parameter number $sy1Index was not found in this file";
        return;
    }
    $val = $sy1Values[$sy1Index];
    if (!isset($map[$val])) {
        echo "\r\nUnable to map sy1 paramter $sy1Index value $val";
        return;
    }
    $deluge[$delugeName] = $map[$val];
}

// --------------------------------------
//
// main processing loop
$first = true;
foreach (glob($inputFiles) as $filename) {

    // open input
    echo $filename.' ';
    $fin = fopen($filename,"rb");
    if ($fin===false) {
        echo "error opening input file\r\n";
        continue;
    }

    // if not first time through, increment output filename
    if (!$first) {
        if (++$outputFileChar == 27) {
            $outputFileNumber++;
            $outputFileChar=0;
        }
    }
    $first=false;

    // open output
    $outFilename = "SYNT".sprintf("%03d",$outputFileNumber);
    if ($outputFileChar!=0)
        $outFilename.=chr(64+$outputFileChar);
    $outFilename.='.XML';
    echo "-> ".$outFilename.' ';
    $fout = fopen($outFilename,"wb");
    if ($fout===false) {
        echo "error opening output file\r\n";
        die;
    }

    // -----
    // read sy1
    $sy1Values = [];    // clear
    fgets($fin);    // this gets the name, but i dont think anything we can do with it.
    fgets($fin);    // colour - we dont care.
    fgets($fin);    // version - we dont care.

    //now get the params
    while(($line=fgets($fin))!==false) {
        $parts = explode(',',$line);
        $sy1Values[trim($parts[0])]=trim($parts[1]);
    }

    // ***************************************************************
    //
    // CONVERSION. time to fiddle!
    //
    // if you are doing anything manually its going to be something like
    //
    // $deluge['KEY'] = $sy1Values[PARAM_NUMBER] ...your_magic_here...
    //
    // scaleValue() takes
    //          SY1_PARAM_NUMBER, SY1_MinValue, SY1_MaxValue
    //          DELUGE_KEY, DELUGE_MinValue, DELUGE_MaxValue
    //          gamma, scaling factor like monitor gamma curve. go smaller than
    //                 1 to bias a faster rise from dmin to dmax, go bigger
    //                 than 1 to have shallower rise. see this image for example
    //                  http://www.normankoren.com/Gamma_lum.gif
    //
    // for the values in <defaultParams> section of the deluge xml file
    // call scaleValueHex() instead to put a hex value into the output
    // as no idea if it will work with decimal numbers
    //
    // $otherPatchCables is a plain string to add in later on.
    //
    //  ***************************************************************
    $deluge = $defaultDeluge;
    $otherPatchCables = '';

    indexToString(0,'osc1_type',$mapOscShape);
    // 45 - fm - dont think there is an equivalent
    // 76 - osc1 detune
    indexToString(1,'osc2_type',$mapOscShape);
    scaleValue(2,0,127,'osc2_transpose',-64,63);
    scaleValue(3,0,127,'osc2_cents',-62,61);

    // <defaultParams> section

//    scaleValueHex(0,0,0'...',0,0);

    // ---------------------------------------------------------
    // this is xml output
$xml=<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<firmwareVersion>1.3.1</firmwareVersion>
<sound>
	<osc1>
		<type>{$deluge['osc1_type']}</type>
		<transpose>{$deluge['osc1_transpose']}</transpose>
		<cents>{$deluge['osc1_cents']}</cents>
		<retrigPhase>{$deluge['osc1_retrigPhase']}</retrigPhase>
	</osc1>
	<osc2>
		<type>{$deluge['osc2_type']}</type>
		<transpose>{$deluge['osc2_transpose']}</transpose>
		<cents>{$deluge['osc2_cents']}</cents>
		<oscillatorSync>{$deluge['osc2_oscillatorSync']}</oscillatorSync>
		<retrigPhase>{$deluge['osc2_retrigPhase']}</retrigPhase>
	</osc2>
	<polyphonic>{$deluge['polyphonic']}</polyphonic>
	<clippingAmount>{$deluge['clippingAmount']}</clippingAmount>
	<voicePriority>{$deluge['voicePriority']}</voicePriority>
	<lfo1>
		<type>{$deluge['lfo1_type']}</type>
		<syncLevel>{$deluge['lfo1_syncLevel']}</syncLevel>
	</lfo1>
	<lfo2>
		<type>{$deluge['lfo2_type']}</type>
	</lfo2>
	<mode>{$deluge['mode']}</mode>
	<unison>
		<num>{$deluge['unison_num']}</num>
		<detune>{$deluge['unison_detune']}</detune>
	</unison>
	<compressor>
		<syncLevel>{$deluge['compressor_syncLevel']}</syncLevel>
		<attack>{$deluge['compressor_attack']}</attack>
		<release>{$deluge['compressor_release']}</release>
	</compressor>
	<lpfMode>{$deluge['lpfMode']}</lpfMode>
	<modFXType>{$deluge['modFXType']}</modFXType>
	<delay>
		<pingPong>{$deluge['pingPong']}</pingPong>
		<analog>{$deluge['analog']}</analog>
		<syncLevel>{$deluge['syncLevel']}</syncLevel>
	</delay>
	<defaultParams>
		<arpeggiatorGate>{$deluge['arpeggiatorGate']}</arpeggiatorGate>
		<portamento>{$deluge['portamento']}</portamento>
		<compressorShape>{$deluge['compressorShape']}</compressorShape>
		<oscAVolume>{$deluge['oscAVolume']}</oscAVolume>
		<oscAPulseWidth>{$deluge['oscAPulseWidth']}</oscAPulseWidth>
		<oscBVolume>{$deluge['oscBVolume']}</oscBVolume>
		<oscBPulseWidth>{$deluge['oscBPulseWidth']}</oscBPulseWidth>
		<noiseVolume>{$deluge['noiseVolume']}</noiseVolume>
		<volume>{$deluge['volume']}</volume>
		<pan>{$deluge['pan']}</pan>
		<lpfFrequency>{$deluge['lpfFrequency']}</lpfFrequency>
		<lpfResonance>{$deluge['lpfResonance']}</lpfResonance>
		<hpfFrequency>{$deluge['hpfFrequency']}</hpfFrequency>
		<hpfResonance>{$deluge['hpfResonance']}</hpfResonance>
		<envelope1>
			<attack>{$deluge['envelope1_attack']}</attack>
			<decay>{$deluge['envelope1_decay']}</decay>
			<sustain>{$deluge['envelope1_sustain']}</sustain>
			<release>{$deluge['envelope1_release']}</release>
		</envelope1>
		<envelope2>
			<attack>{$deluge['envelope2_attack']}</attack>
			<decay>{$deluge['envelope2_decay']}</decay>
			<sustain>{$deluge['envelope2_sustain']}</sustain>
			<release>{$deluge['envelope2_release']}</release>
		</envelope2>
		<lfo1Rate>{$deluge['lfo1Rate']}</lfo1Rate>
		<lfo2Rate>{$deluge['lfo2Rate']}</lfo2Rate>
		<modulator1Amount>{$deluge['modulator1Amount']}</modulator1Amount>
		<modulator1Feedback>{$deluge['modulator1Feedback']}</modulator1Feedback>
		<modulator2Amount>{$deluge['modulator2Amount']}</modulator2Amount>
		<modulator2Feedback>{$deluge['modulator2Feedback']}</modulator2Feedback>
		<carrier1Feedback>{$deluge['carrier1Feedback']}</carrier1Feedback>
		<carrier2Feedback>{$deluge['carrier2Feedback']}</carrier2Feedback>
		<modFXRate>{$deluge['modFXRate']}</modFXRate>
		<modFXDepth>{$deluge['modFXDepth']}</modFXDepth>
		<delayRate>{$deluge['delayRate']}</delayRate>
		<delayFeedback>{$deluge['delayFeedback']}</delayFeedback>
		<reverbAmount>{$deluge['reverbAmount']}</reverbAmount>
		<arpeggiatorRate>{$deluge['arpeggiatorRate']}</arpeggiatorRate>
		<patchCables>
			<patchCable>
				<source>velocity</source>
				<destination>volume</destination>
				<amount>0x3FFFFFE8</amount>
			</patchCable>
            $otherPatchCables
		</patchCables>
		<stutterRate>0x00000000</stutterRate>
		<sampleRateReduction>0x80000000</sampleRateReduction>
		<bitCrush>0x80000000</bitCrush>
		<equalizer>
			<bass>0xE6666654</bass>
			<treble>0x2E147AC2</treble>
			<bassFrequency>0x00000000</bassFrequency>
			<trebleFrequency>0x00000000</trebleFrequency>
		</equalizer>
		<modFXOffset>0x00000000</modFXOffset>
		<modFXFeedback>0x00000000</modFXFeedback>
	</defaultParams>
	<midiKnobs>
	</midiKnobs>
	<modKnobs>
		<modKnob>
			<controlsParam>pan</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>volumePostFX</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>lpfResonance</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>lpfFrequency</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>env1Release</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>env1Attack</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>delayFeedback</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>delayRate</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>reverbAmount</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>volumePostReverbSend</controlsParam>
			<patchAmountFromSource>compressor</patchAmountFromSource>
		</modKnob>
		<modKnob>
			<controlsParam>pitch</controlsParam>
			<patchAmountFromSource>lfo1</patchAmountFromSource>
		</modKnob>
		<modKnob>
			<controlsParam>lfo1Rate</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>portamento</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>stutterRate</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>bitcrushAmount</controlsParam>
		</modKnob>
		<modKnob>
			<controlsParam>sampleRateReduction</controlsParam>
		</modKnob>
	</modKnobs>
</sound>
XML;

    fwrite($fout, $xml);
    fclose($fout);
    fclose($fin);
    echo "done\r\n";
}
