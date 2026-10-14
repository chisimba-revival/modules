<?php
/** Prevent return of the superseded instructor/ExtJS module. @author Derek Keats */
if (is_dir(dirname(__DIR__).'/contextinstructor')) throw new RuntimeException('Retired Context Instructor module returned');
echo "PASS: superseded Context Instructor module absent\n";
