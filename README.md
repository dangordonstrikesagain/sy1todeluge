# sy1todeluge
Converter for Synth1 .sy1 patch files to Deluge patch files

totally work in progress, just a framework at the moment, waiting for the conversions to be put in, so over to you guys to fiddle with it.

as it stands it just maps the osc 1 & 2 type, plus osc 2 tuning

a guide to sy1 parameter mappings can be found at https://sound.eti.pg.gda.pl/student/eim/doc/Synth1.pdf

usage from command line:

php sy1todeluge.php <inputfile> [outputFileNumber]

inputfile can take wildcards, but i have found on the ubuntu for windows
you need to put the wildcard filename in quotes, i.e.

php sy1todeluge.php "*.sy1" 600

otherwise it causes problems. outputfilenumber will start say SYNT600.XML then SYNT600A.XML etc

have a look from line 209 onwards to see the conversions going on and have a fiddle.

modulations will probably need to have <patchCable>s added
