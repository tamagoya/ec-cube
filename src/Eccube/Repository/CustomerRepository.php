<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Doctrine\Query\Queries;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Util\StringUtil;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * CustomerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CustomerRepository extends AbstractRepository
{
    /**
     * @var Queries
     */
    protected $queries;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * CustomerRepository constructor.
     *
     * @param RegistryInterface $registry
     * @param Queries $queries
     * @param EntityManagerInterface $entityManager
     * @param OrderRepository $orderRepository
     * @param EncoderFactoryInterface $encoderFactory
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        RegistryInterface $registry,
        Queries $queries,
        EntityManagerInterface $entityManager,
        OrderRepository $orderRepository,
        EncoderFactoryInterface $encoderFactory,
        EccubeConfig $eccubeConfig
    ) {
        parent::__construct($registry, Customer::class);

        $this->queries = $queries;
        $this->entityManager = $entityManager;
        $this->orderRepository = $orderRepository;
        $this->encoderFactory = $encoderFactory;
        $this->eccubeConfig = $eccubeConfig;
    }

    public function newCustomer()
    {
        $CustomerStatus = $this->getEntityManager()
            ->find(CustomerStatus::class, CustomerStatus::PROVISIONAL);

        $Customer = new \Eccube\Entity\Customer();
        $Customer
            ->setStatus($CustomerStatus);

        return $Customer;
    }

    public function getQueryBuilderBySearchData($searchData)
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c');

        if (isset($searchData['multi']) && StringUtil::isNotBlank($searchData['multi'])) {
            //スペース除去
            $clean_key_multi = preg_replace('/\s+|[　]+/u', '', $searchData['multi']);
            $id = preg_match('/^\d{0,10}$/', $clean_key_multi) ? $clean_key_multi : null;
            $qb
                ->andWhere('c.id = :customer_id OR CONCAT(c.name01, c.name02) LIKE :name OR CONCAT(c.kana01, c.kana02) LIKE :kana OR c.email LIKE :email')
                ->setParameter('customer_id', $id)
                ->setParameter('name', '%'.$clean_key_multi.'%')
                ->setParameter('kana', '%'.$clean_key_multi.'%')
                ->setParameter('email', '%'.$clean_key_multi.'%');
        }

        // Pref
        if (!empty($searchData['pref']) && $searchData['pref']) {
            $qb
                ->andWhere('c.Pref = :pref')
                ->setParameter('pref', $searchData['pref']->getId());
        }

        // sex
        if (!empty($searchData['sex']) && count($searchData['sex']) > 0) {
            $sexs = [];
            foreach ($searchData['sex'] as $sex) {
                $sexs[] = $sex->getId();
            }

            $qb
                ->andWhere($qb->expr()->in('c.Sex', ':sexs'))
                ->setParameter('sexs', $sexs);
        }

        if (!empty($searchData['birth_month']) && $searchData['birth_month']) {
            $qb
                ->andWhere('EXTRACT(MONTH FROM c.birth) = :birth_month')
                ->setParameter('birth_month', $searchData['birth_month']);
        }

        // birth
        if (!empty($searchData['birth_start']) && $searchData['birth_start']) {
            $qb
                ->andWhere('c.birth >= :birth_start')
                ->setParameter('birth_start', $searchData['birth_start']);
        }
        if (!empty($searchData['birth_end']) && $searchData['birth_end']) {
            $date = clone $searchData['birth_end'];
            $date->modify('+1 days');
            $qb
                ->andWhere('c.birth < :birth_end')
                ->setParameter('birth_end', $date);
        }

        // tel
        if (isset($searchData['phone_number']) && StringUtil::isNotBlank($searchData['phone_number'])) {
            $tel = preg_replace('/[^0-9]/ ', '', $searchData['phone_number']);
            $qb
                ->andWhere('c.phone_number LIKE :phone_number')
                ->setParameter('phone_number', '%'.$tel.'%');
        }

        // buy_total
        if (isset($searchData['buy_total_start']) && StringUtil::isNotBlank($searchData['buy_total_start'])) {
            $qb
                ->andWhere('c.buy_total >= :buy_total_start')
                ->setParameter('buy_total_start', $searchData['buy_total_start']);
        }
        if (isset($searchData['buy_total_end']) && StringUtil::isNotBlank($searchData['buy_total_end'])) {
            $qb
                ->andWhere('c.buy_total <= :buy_total_end')
                ->setParameter('buy_total_end', $searchData['buy_total_end']);
        }

        // buy_times
        if (!empty($searchData['buy_times_start']) && $searchData['buy_times_start']) {
            $qb
                ->andWhere('c.buy_times >= :buy_times_start')
                ->setParameter('buy_times_start', $searchData['buy_times_start']);
        }
        if (!empty($searchData['buy_times_end']) && $searchData['buy_times_end']) {
            $qb
                ->andWhere('c.buy_times <= :buy_times_end')
                ->setParameter('buy_times_end', $searchData['buy_times_end']);
        }

        // create_date
        if (!empty($searchData['create_date_start']) && $searchData['create_date_start']) {
            $qb
                ->andWhere('c.create_date >= :create_date_start')
                ->setParameter('create_date_start', $searchData['create_date_start']);
        }
        if (!empty($searchData['create_date_end']) && $searchData['create_date_end']) {
            $date = clone $searchData['create_date_end'];
            $date->modify('+1 days');
            $qb
                ->andWhere('c.create_date < :create_date_end')
                ->setParameter('create_date_end', $date);
        }

        // update_date
        if (!empty($searchData['update_date_start']) && $searchData['update_date_start']) {
            $qb
                ->andWhere('c.update_date >= :update_date_start')
                ->setParameter('update_date_start', $searchData['update_date_start']);
        }
        if (!empty($searchData['update_date_end']) && $searchData['update_date_end']) {
            $date = clone $searchData['update_date_end'];
            $date->modify('+1 days');
            $qb
                ->andWhere('c.update_date < :update_date_end')
                ->setParameter('update_date_end', $date);
        }

        // last_buy
        if (!empty($searchData['last_buy_start']) && $searchData['last_buy_start']) {
            $qb
                ->andWhere('c.last_buy_date >= :last_buy_start')
                ->setParameter('last_buy_start', $searchData['last_buy_start']);
        }
        if (!empty($searchData['last_buy_end']) && $searchData['last_buy_end']) {
            $date = clone $searchData['last_buy_end'];
            $date->modify('+1 days');
            $qb
                ->andWhere('c.last_buy_date < :last_buy_end')
                ->setParameter('last_buy_end', $date);
        }

        // status
        if (!empty($searchData['customer_status']) && count($searchData['customer_status']) > 0) {
            $qb
                ->andWhere($qb->expr()->in('c.Status', ':statuses'))
                ->setParameter('statuses', $searchData['customer_status']);
        }

        // buy_product_name
        if (isset($searchData['buy_product_name']) && StringUtil::isNotBlank($searchData['buy_product_name'])) {
            $qb
                ->leftJoin('c.Orders', 'o')
                ->leftJoin('o.OrderItems', 'oi')
                ->andWhere('oi.product_name LIKE :buy_product_name')
                ->setParameter('buy_product_name', '%'.$searchData['buy_product_name'].'%');
        }

        // Order By
        $qb->addOrderBy('c.update_date', 'DESC');

        return $this->queries->customize(QueryKey::CUSTOMER_SEARCH, $qb, $searchData);
    }

    /**
     * ユニークなシークレットキーを返す.
     *
     * @return string
     */
    public function getUniqueSecretKey()
    {
        do {
            $key = StringUtil::random(32);
            $Customer = $this->findOneBy(['secret_key' => $key]);
        } while ($Customer);

        return $key;
    }

    /**
     * ユニークなパスワードリセットキーを返す
     *
     * @return string
     */
    public function getUniqueResetKey()
    {
        do {
            $key = StringUtil::random(32);
            $Customer = $this->findOneBy(['reset_key' => $key]);
        } while ($Customer);

        return $key;
    }

    /**
     * 仮会員をシークレットキーで検索する.
     *
     * @param $secretKey
     *
     * @return null|Customer 見つからない場合はnullを返す.
     */
    public function getProvisionalCustomerBySecretKey($secretKey)
    {
        return $this->findOneBy([
            'secret_key' => $secretKey,
            'Status' => CustomerStatus::PROVISIONAL,
        ]);
    }

    /**
     * 本会員をemailで検索する.
     *
     * @param $email
     *
     * @return null|Customer 見つからない場合はnullを返す.
     */
    public function getRegularCustomerByEmail($email)
    {
        return $this->findOneBy([
            'email' => $email,
            'Status' => CustomerStatus::REGULAR,
        ]);
    }

    /**
     * 本会員をリセットキーで検索する.
     *
     * @param $resetKey
     *
     * @return null|Customer 見つからない場合はnullを返す.
     */
    public function getRegularCustomerByResetKey($resetKey)
    {
        return $this->createQueryBuilder('c')
            ->where('c.reset_key = :reset_key AND c.Status = :status AND c.reset_expire >= :reset_expire')
            ->setParameter('reset_key', $resetKey)
            ->setParameter('status', CustomerStatus::REGULAR)
            ->setParameter('reset_expire', new \DateTime())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * リセット用パスワードを生成する.
     *
     * @return string
     */
    public function getResetPassword()
    {
        return StringUtil::random(8);
    }

    /**
     * 会員の初回購入時間、購入時間、購入回数、購入金額を更新する
     *
     * @param $app
     * @param  Customer $Customer
     * @param  $orderStatusId
     * @param  $isNewOrder
     */
    public function updateBuyData(Customer $Customer, $orderStatusId)
    {
        // 会員の場合、初回購入時間・購入時間・購入回数・購入金額を更新

        $arr = [
            OrderStatus::NEW,
            OrderStatus::PAY_WAIT,
            OrderStatus::BACK_ORDER,
            OrderStatus::DELIVERED,
            OrderStatus::PAID,
        ];

        $result = $this->orderRepository->getCustomerCount($Customer, $arr);

        if (!empty($result)) {
            $data = $result[0];

            $now = new \DateTime();

            $firstBuyDate = $Customer->getFirstBuyDate();
            if (empty($firstBuyDate)) {
                $Customer->setFirstBuyDate($now);
            }

            if ($orderStatusId == OrderStatus::CANCEL ||
                $orderStatusId == OrderStatus::PENDING ||
                $orderStatusId == OrderStatus::PROCESSING
            ) {
                // キャンセル、決済処理中、購入処理中は購入時間は更新しない
            } else {
                $Customer->setLastBuyDate($now);
            }

            $Customer->setBuyTimes($data['buy_times']);
            $Customer->setBuyTotal($data['buy_total']);
        } else {
            // 受注データが存在しなければ初期化
            $Customer->setFirstBuyDate(null);
            $Customer->setLastBuyDate(null);
            $Customer->setBuyTimes(0);
            $Customer->setBuyTotal(0);
        }

        $this->entityManager->persist($Customer);
        $this->entityManager->flush();
    }

    /**
     * 仮会員, 本会員の会員を返す.
     * Eccube\Entity\CustomerのUniqueEntityバリデーションで使用しています.
     *
     * @param array $criteria
     *
     * @return Customer[]
     */
    public function getNonWithdrawingCustomers(array $criteria = [])
    {
        $criteria['Status'] = [
            CustomerStatus::PROVISIONAL,
            CustomerStatus::REGULAR,
        ];

        return $this->findBy($criteria);
    }
}
