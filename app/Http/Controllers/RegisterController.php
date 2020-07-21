<?php

namespace App\Http\Controllers;

use App\Actions\StoreSpaceInSessionAction;
use App\Actions\SendVerificationMailAction;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
// use App\Http\Controllers\Controller;

use App\Repositories\CurrencyRepository;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\SpaceRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    private $currencyRepository;
    private $userRepository;
    private $spaceRepository;
    private $loginAttemptRepository;

    public function __construct(
        CurrencyRepository $currencyRepository,
        UserRepository $userRepository,
        SpaceRepository $spaceRepository,
        LoginAttemptRepository $loginAttemptRepository
    ) {
        $this->currencyRepository = $currencyRepository;
        $this->userRepository = $userRepository;
        $this->spaceRepository = $spaceRepository;
        $this->loginAttemptRepository = $loginAttemptRepository;
    }

    public function index()
    {
        return view('register', [
            'currencies' => $this->currencyRepository->getKeyValueArray()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->userRepository->getValidationRulesForRegistration());

        $user = $this->userRepository->create($request->name, $request->email, $request->password);
        $space = $this->spaceRepository->create($request->currency, $user->name . '\'s Space');
        $user->spaces()->attach($space->id, ['role' => 'admin']);

        (new SendVerificationMailAction())->execute($user->id);

        Auth::loginUsingId($user->id);

        $this->loginAttemptRepository->create($user->id, $request->ip(), false);

        (new StoreSpaceInSessionAction())->execute($user->spaces[0]->id);

        return redirect()
            ->route('dashboard');
    }
}
