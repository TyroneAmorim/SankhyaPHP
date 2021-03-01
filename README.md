# SankhyaPHP

Classe php para executar queries sql pela api do sistema Sankhya.


```php
$sc = new SankhyaPHP("HOST", "USER", "PASS");

$sc->login();

$query = $sc->query("SELECT * FROM TGFPAR");

$sc->logout();
```
