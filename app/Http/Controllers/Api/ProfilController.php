<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ModeyWithdrawalRequest;
use App\Http\Requests\Api\UpdateInforUserRequest;
use App\Http\Requests\Api\UpdateProfilRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Http\Requests\Api\VerifyIdentityRequest;
use App\Http\Requests\UpdateProfilRequest as RequestsUpdateProfilRequest;

class ProfilController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Profil récupéré avec succès',
            'data'   => $request->user() ? Storage::disk('public')->url($request->user()->profile_photo):null
        ], 200);
    }

    public function update(UpdateInforUserRequest $request)
    {
        $user = $request->user();

        // Utilisez validated() sous forme de MÉTHODE avec des parenthèses ()
        $user->update($request->validated());  

        return response()->json([
            'status'  => true,
            'message' => 'Informations mises à jour avec succès.',
            'user'    => $user
        ], 200);
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2Mo
        ]);

        $user = $request->user();

        // Supprimer l'ancienne photo si elle existe
        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)){
            Storage::disk('public')->delete($user->profile_photo);
        }

        // Enregistrer la nouvelle photo dans storage/app/public/profiles
        $path = $request->file('profile_photo')->store('profiles', 'public');

        // Mettre à jour en BDD
        $user->update(['profile_photo' => $path]);
        $user->save();

        return response()->json([
            'status'     => true,
            'message'    => 'Photo de profil mise à jour avec succès.',
            'photo_url'  => asset('storage/' . $path)
        ], 200);
    }

    /**
     * Changer le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'confirmed'],
        ]);

        $user = $request->user();

        // Vérifier l'ancien mot de passe
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Le mot de passe actuel est incorrect.'
            ], 422);
        }

        // Mise à jour du nouveau mot de passe
        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Mot de passe modifié avec succès.'
        ], 200);
    }




    public function verifyIdentity(VerifyIdentityRequest $request)
    {
        $user = $request->user();

        // 1. VÉRIFICATION DU NUMÉRO EN BDD (Contrôle anti-doublon)
        $nationalIdExists = User::where('national_id', $request->national_id)
                                ->where('id', '!=', $user->id)
                                ->exists();

        if ($nationalIdExists) {
            return response()->json([
                'status'  => false,
                'message' => 'Ce numéro de pièce d\'identité est déjà rattaché à un autre compte.'
            ], 422);
        }

        // 2. EXTRACTION DU TEXTE DE LA CNI VIA API OCR.SPACE
        $idDocumentFile = $request->file('id_document');

        try {
            // Envoi de la photo de la carte d'identité à l'API
            $response = Http::withHeaders([
                'apikey' => env('OCR_SPACE_API_KEY'),
            ])->attach(
                'file',
                file_get_contents($idDocumentFile->getPathname()),
                $idDocumentFile->getClientOriginalName()
            )->post('https://api.ocr.space/parse/image', [
                'language' => 'fre', // Analyse du texte en français
                'isOverlayRequired' => 'false',
                'OCREngine' => '2',  // Moteur plus rapide et précis
            ]);

            $responseData = $response->json();

            // Vérification des erreurs renvoyées par l'API OCR
            if (isset($responseData['IsErroredOnProcessing']) && $responseData['IsErroredOnProcessing']) {
                $errorMessage = $responseData['ErrorMessage'][0] ?? 'Erreur lors de la lecture du document.';
                return response()->json([
                    'status'  => false,
                    'message' => 'Erreur OCR : ' . $errorMessage
                ], 422);
            }

            // Récupération du texte extrait par l'API
            $extractedText = $responseData['ParsedResults'][0]['ParsedText'] ?? '';

            // 3. CONTRÔLE : Le numéro saisi est-il présent dans le texte lu sur l'image ?
            if (!str_contains($extractedText, $request->national_id)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Le numéro de pièce d\'identité saisi ne semble pas correspondre au document fourni.'
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Erreur de connexion avec le service OCR : ' . $e->getMessage()
            ], 500);
        }

        // 4. STOCKAGE SÉCURISÉ DES FICHIERS
        // Carte d'identité dans un dossier privé
        if ($user->national_id_photo && Storage::disk('local')->exists($user->national_id_photo)) {
            Storage::disk('local')->delete($user->national_id_photo);
        }
        $storedIdPath = $idDocumentFile->store('identity_documents', 'local');

        // Photo de profil / selfie dans le stockage public
        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }
        $storedProfilePath = $request->file('selfie')->store('profiles', 'public');

        // 5. MISE À JOUR DE L'UTILISATEUR DANS LA BDD
        $user->update([
            'national_id'       => $request->national_id,
            'national_id_photo' => $storedIdPath,
            'profile_photo'     => $storedProfilePath,
            'status'            => 'actif',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Identité vérifiée et validée avec succès !',
            'data'    => [
                'user'      => $user,
                'photo_url' => asset('storage/' . $storedProfilePath)
            ]
        ], 200);
    }

}
