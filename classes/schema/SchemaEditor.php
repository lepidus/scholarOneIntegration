<?php

use PKP\plugins\Hook;

class SchemaEditor
{
    public function editEventLogSchema($hookName, $params)
    {
        $schema = &$params[0];
        $schema->properties->{'errorMessage'} = (object) [
            'type' => 'string',
            'apiSummary' => true,
            'validation' => ['nullable'],
        ];
        return Hook::CONTINUE;
    }
}
