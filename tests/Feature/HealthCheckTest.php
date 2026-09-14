<?php

// Docker healthchecks hit this without a tenant domain
it('responds to health checks without a tenant', function () {
    $this->get('http://no-such-tenant.test/up')->assertOk();
});
