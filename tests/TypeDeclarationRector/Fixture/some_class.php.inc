<?php

namespace Tzmfreedom\TypeTracer\Tests\TypeDeclarationRector\Fixture;

class Foo
{
    public function bar($i, string $j, $k)
    {

    }
}

?>
-----
<?php

namespace Tzmfreedom\TypeTracer\Tests\TypeDeclarationRector\Fixture;

class Foo
{
    public function bar(object|string|int|float|bool|App\Models\Hoge|null $i, string $j, $k)
    {

    }
}

?>
