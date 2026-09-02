<?php
/** JSON response for Essay background draft saves. @author Derek Keats */
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($draftResponse, JSON_UNESCAPED_SLASHES);
