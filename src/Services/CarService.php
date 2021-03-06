<?php

namespace App\Services;

use App\Entity\Car;
use App\Entity\User;
use App\Event\CarEvent;
use App\Mapper\CarMapper;
use App\Repository\CarRepository;
use App\Request\GetCarRequest;
use App\Transfer\CarTransfer;
use App\Transformer\CarTransformer;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CarService
{
    /**
     * @var CarTransfer
     */
    private CarTransfer $carTransfer;
    /**
     * @var CarRepository
     */
    private CarRepository $carRepository;

    /**
     * @var CarTransformer
     */
    private CarTransformer $carTransformer;

    /**
     * @var CarMapper
     */
    private CarMapper $carMapper;

    /**
     * @var ImageService
     */
    private ImageService $imageService;

    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $dispatcher;

    /**
     * CarService constructor.
     *
     * @param CarTransfer $carTransfer
     * @param CarRepository $carRepository
     * @param EventDispatcherInterface $dispatcher
     * @param ImageService $imageService
     * @param CarTransformer $carTransformer
     * @param CarMapper $carMapper
     */
    public function __construct(
        CarTransfer $carTransfer,
        CarRepository $carRepository,
        EventDispatcherInterface $dispatcher,
        ImageService $imageService,
        CarTransformer $carTransformer,
        CarMapper $carMapper
    ) {
        $this->carTransfer = $carTransfer;
        $this->imageService = $imageService;
        $this->carRepository = $carRepository;
        $this->dispatcher = $dispatcher;
        $this->carTransformer = $carTransformer;
        $this->carMapper = $carMapper;
    }

    public function getCar(int $carId): ?Car
    {
        return $this->carRepository->find($carId);
    }

    public function getAllCars(GetCarRequest $requestData): array
    {
        $cars = $this->carRepository->getAll($requestData);
        $carsData = [];
        foreach ($cars as $car) {
            $carTransform = $this->carTransformer->transform($car);
            $carsData[] = $carTransform;
        }

        return $carsData;
    }

    /**
     * @param array $requestData
     * @param User $user
     * @param int $thumbnailId
     * @return Car
     */
    public function addCar(array $requestData, User $user, int $thumbnailId): Car
    {
        $car = $this->carTransfer->transfer($requestData);
        $image = $this->imageService->getImage($thumbnailId);
        $car->setThumbnail($image);
        $car->setCreatedUser($user);
        $this->carRepository->add($car, true);

        $event = new CarEvent($car);
        $this->dispatcher->dispatch($event, CarEvent::SET);

        return $car;
    }

    /**
     * @param Car $car
     * @param array $requestData
     * @return Car
     */
    public function updateCar(Car $car, array $requestData): Car
    {
        $carUpdate = $this->carMapper->mapping($car, $requestData);
        $this->carRepository->add($carUpdate, true);

        $event = new CarEvent($car);
        $this->dispatcher->dispatch($event, CarEvent::UPDATE);

        return $carUpdate;
    }

    /**
     * @param Car $car
     * @return void
     */
    public function hardDeleteCar(Car $car): void
    {
        $this->carRepository->remove($car, true);

        $event = new CarEvent($car);
        $this->dispatcher->dispatch($event, CarEvent::DELETE);
    }

    /**
     * @throws EntityNotFoundException
     */
    public function softDeleteCar(int $carId): Car
    {
        $car = $this->carRepository->find($carId);
        if (!$car) {
            throw new EntityNotFoundException('Car with id ' . $carId . ' does not exist!');
        }
        $car->setDeletedAt(new \DateTimeImmutable());
        $this->carRepository->add($car, true);

        $event = new CarEvent($car);
        $this->dispatcher->dispatch($event, CarEvent::DELETE);

        return $car;
    }
}
