<?php

namespace ApiBundle\Command;

use ccxt\bitfinex2;
use ccxt\bitmex;
use ccxt\livecoin;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ApiTradeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('api:trade')
            ->setDescription('check cost and start trade')
            ->addOption('wallet1', null, InputOption::VALUE_REQUIRED, 'first wallet')
            ->addOption("wallet2", null, InputOption::VALUE_REQUIRED, "second wallet")
         //   ->addOption("percent", null, InputOption::VALUE_REQUIRED, "percent rate")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        date_default_timezone_set("UTC");

        $first_wallet = $input->getOption("wallet1");
        $second_wallet = $input->getOption("wallet2");
        //$percent_rate = $input->getOption("percent");

        try {
            $first_record = $this->getContainer()->
                get("doctrine")
                ->getManager()
                ->getRepository('ApiBundle:ApiKey')
                ->findOneBy(array(
                    "wallet" => $first_wallet,
                    )
                );

            $second_record = $this->getContainer()->
            get("doctrine")
                ->getManager()
                ->getRepository('ApiBundle:ApiKey')
                ->findOneBy(array(
                        "wallet" => $second_wallet,
                    )
                );

            /**
            $bitfinex = new bitfinex2();
            $bitfinex->apiKey = $first_record->getKey();
            $bitfinex->secret =$first_record->getSecretKey();
            //print_r($bitfinex->fetch_balance());
            $bitfinex->load_markets();
            $markets = $bitfinex->market($first_record->getExchange());
            $bitfinex_orders = $bitfinex->fetch_order_book($bitfinex->symbols[0]);
            print_r($bitfinex_orders);
            //print_r($markets);
            **/
            $bitmex = new bitmex();
            $bitmex->apiKey = $second_record->getKey();
            $bitmex->secret =$second_record->getSecretKey();
            //print_r($bitmex->fetch_balance()); работает

            $bitmex->load_markets();
            $markets = $bitmex->market($second_record->getExchange());
            //$bitmex_orders = $bitmex->fetch_order_book($bitmex->symbols[0]);
            $bitmex_bid = $markets['info']['bidPrice'];
            $bitmex_ask = $markets['info']['askPrice'];


            $livecoin = new livecoin();
            $livecoin->apiKey = $first_record->getKey();
            $livecoin->secret = $first_record->getSecretKey();

            //print_r($livecoin->fetch_balance()); работает
            $livecoin->load_markets(true);
            $markets = $livecoin->market($first_record->getExchange());
            //$bitfinex_orders = $bitfinex->fetch_order_book($bitfinex->symbols[0]);

            $livecoin_bid = $markets["info"]['best_bid'];
            $livecoin_ask = $markets['info']['best_ask'];

            $result = array(
                "pare" => $first_record->getExchange(),
                "first wallet" => $first_wallet,
                "second_wallet" => $second_wallet,
                "livecoin bid" => $livecoin_bid,
                "livecoin ask" => $livecoin_ask,
                "bitmex bid" => $bitmex_bid,
                "bitmex ask" => $bitmex_ask,
                "bid" => $livecoin_bid - $bitmex_bid,
                "ask" => $livecoin_ask - $bitmex_ask,
                "bid  spread" => ($livecoin_bid / $bitmex_bid - 1) * 100,
                "ask spread" => ($livecoin_ask / $bitmex_ask -1) * 100,
            );

            print_r($result);



        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            $output->writeln($e->getFile());
            $output->writeln($e->getLine());
        }
    }

}
