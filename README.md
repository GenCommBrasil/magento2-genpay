# Módulo de Pagamento para Magento 2 para [Rakuten Connector](https://digitalcommerce.rakuten.com.br/solucoes/rakuten-connector/) Payment Gateway (Magento 2.3) 

Bem vindo ao repositório da extensão Magento para o Rakuten Connector

![Rakuten Pay](https://raw.githubusercontent.com/RakutenBrasil/magento1-rakuten-pay/master/images/logo-rakuten-pay.png)

## Instalação

Execute em seu shell:

```
$ composer require rakuten/magento2-connector
$ php bin/magento module:enable Rakuten_RakutenPay
$ php bin/magento setup:upgrade
$ php bin/magento setup:di:compile
$ php bin/magento cache:flush
```

Conheça mais a respeito de nossa solução de pagamentos [Rakuten Pay](https://digitalcommerce.rakuten.com.br/produtos/pagamentos-rakuten-pay/) e sobre o programa de parceria para desenvolvedores e agências [Rakuten Pay para Devs](https://digitalcommerce.rakuten.com.br/rakuten-pay-dev/)